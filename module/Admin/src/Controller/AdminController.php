<?php

namespace Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use User\Entity\User;
use Application\Util\InputSanitizer;
use Game\Service\GameManager;

class AdminController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $gameManager;

    public function __construct($entityManager, $authService, $gameManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->gameManager = $gameManager;
    }


    public function gamesAction()
    {
        $currentUser = $this->authService->getIdentity();
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        $games = $this->entityManager->getRepository(Game::class)->findBy([], ['date' => 'DESC']);
        if ($request->isPost()) {
            $data = InputSanitizer::cleanArray($request->getPost()->toArray());
            if (!empty($data['date']) && !empty($data['player_max']) && isset($data['status'])) {

                $newGame = $this->gameManager->addGame($data);
                if($newGame){
                    $this->flashMessenger()->addSuccessMessage('La partie a bien été ajoutée.');
                    return $this->redirect()->toRoute('admin-games');
                }
                $this->flashMessenger()->addErrorMessage('Une erreur est survenu.');
                return $this->redirect()->toRoute('admin-games');
            }
        }
        $view = new ViewModel([
            "games"=>$games,
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-games');
        $view->setTemplate('admin/games');
        return $view;
    }


    public function editGameAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $id = InputSanitizer::cleanInt($this->params()->fromRoute('id'));
        $game = $this->entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            $this->flashMessenger()->addErrorMessage('Partie introuvable.');
            return $this->redirect()->toRoute('admin-games');
        }
        $registers = $this->entityManager->getRepository(GameRegister::class)->findBy([
            'game' => $game->getIdGame(),
            'status' => GameRegister::STATUS_ACTIVE,
        ]);
        $players = $registers;

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = InputSanitizer::cleanArray($request->getPost()->toArray());

            if (!empty($data['date']) && !empty($data['player_max']) && isset($data['status'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $data['date']);
                if ($date) {
                    $game = $this->gameManager->editGame($game, $data);
                    if($game){
                        $this->flashMessenger()->addSuccessMessage('La partie a bien été modifiée.');
                        return $this->redirect()->toRoute('admin-games');
                    }else{
                          $this->flashMessenger()->addErrorMessage('Une erreur est survenu.');
                    }

                } else {
                    $this->flashMessenger()->addErrorMessage('Format de date invalide.');
                }
            }
        }

        $view = new ViewModel([
            "game"=>$game,
            'players'=>$players,
            'unvalidatedRegisters' => $this->entityManager->getRepository(GameRegister::class)->findUnvalidatedRegisters($game),
            'pendingQueue' => $this->gameManager->getPendingQueue($game),
            'registersToQueue' => $this->gameManager->findRegistersToQueue($game),
        ]);
        $view->setTemplate('admin/edit-game');
        return $view;
    }

    public function deleteGameAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = InputSanitizer::cleanInt($request->getPost('id'));
            $game = $this->entityManager->getRepository(Game::class)->find($id);
            if ($game) {
                $delete = $this->gameManager->deleteGame($game);
                $this->flashMessenger()->addSuccessMessage('Partie supprimée avec succès.');
            } else {
                $this->flashMessenger()->addErrorMessage('Partie introuvable.');
            }
        }

        return $this->redirect()->toRoute('admin-games');
    }

    /**
     * Desinscription d'un joueur par un admin, depuis la fiche d'une partie.
     * Distincte de la desinscription par le joueur lui-meme : aucun
     * remboursement n'est declenche automatiquement.
     */
    public function unregisterPlayerAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-games');
        }

        $id = InputSanitizer::cleanInt($request->getPost('id'));
        $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
            'idregister' => $id,
            'status' => [GameRegister::STATUS_ACTIVE, GameRegister::STATUS_PENDING],
        ]);
        if (!$register) {
            $this->flashMessenger()->addErrorMessage('Inscription introuvable.');
            return $this->redirect()->toRoute('admin-games');
        }

        $register->setStatus(GameRegister::STATUS_CANCELLED);
        $register->setArrivedNumber(0);
        $this->entityManager->flush();

        $user = $register->getUser();
        $this->flashMessenger()->addSuccessMessage(
            $user->getFirstname() . ' ' . $user->getLastname() . ' a été désinscrit de la partie.'
        );
        if ((int) $register->getPaid() === 1) {
            $this->flashMessenger()->addWarningMessage("Cette inscription était payée : le remboursement éventuel est à faire manuellement.");
        }

        return $this->redirect()->toRoute('admin-edit-game', ['id' => $register->getGame()->getIdGame()]);
    }

    /**
     * Passe un inscrit en file d'attente : il perd sa place et devra confirmer
     * sa venue (ou etre reinscrit depuis la file par un admin).
     */
    public function queuePlayerAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-games');
        }

        $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
            'idregister' => InputSanitizer::cleanInt($request->getPost('id')),
            'status' => GameRegister::STATUS_ACTIVE,
        ]);
        if (!$register) {
            $this->flashMessenger()->addErrorMessage('Inscription introuvable.');
            return $this->redirect()->toRoute('admin-games');
        }

        $register->setStatus(GameRegister::STATUS_PENDING);
        $register->setArrivedNumber(0);
        $this->entityManager->flush();

        $user = $register->getUser();
        $this->flashMessenger()->addSuccessMessage(
            $user->getFirstname() . ' ' . $user->getLastname() . " est passé en file d'attente."
        );
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $register->getGame()->getIdGame()]);
    }

    /**
     * Desinscrit d'une partie tous les joueurs dont l'email n'est pas valide.
     * Aucun remboursement n'est declenche.
     */
    public function unregisterUnvalidatedAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-games');
        }

        $game = $this->entityManager->getRepository(Game::class)->find(InputSanitizer::cleanInt($request->getPost('id')));
        if (!$game) {
            $this->flashMessenger()->addErrorMessage('Partie introuvable.');
            return $this->redirect()->toRoute('admin-games');
        }

        $registers = $this->entityManager->getRepository(GameRegister::class)->findUnvalidatedRegisters($game);
        foreach ($registers as $register) {
            $register->setStatus(GameRegister::STATUS_CANCELLED);
            $register->setArrivedNumber(0);
        }
        $this->entityManager->flush();

        $this->flashMessenger()->addSuccessMessage(count($registers) . ' joueur(s) sans email validé désinscrit(s) de la partie.');
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $game->getIdGame()]);
    }

    /**
     * Inscrit un joueur depuis la file d'attente, dans la limite des places.
     */
    public function confirmPendingAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-games');
        }

        $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
            'idregister' => InputSanitizer::cleanInt($request->getPost('id')),
            'status' => GameRegister::STATUS_PENDING,
        ]);
        if (!$register) {
            $this->flashMessenger()->addErrorMessage("Inscription en file d'attente introuvable.");
            return $this->redirect()->toRoute('admin-games');
        }

        $user = $register->getUser();
        $name = $user->getFirstname() . ' ' . $user->getLastname();
        switch ($this->gameManager->adminConfirmPendingRegister($register)) {
            case GameManager::RESULT_CONFIRMED:
                $this->flashMessenger()->addSuccessMessage($name . ' est inscrit à la partie.');
                break;
            case GameManager::RESULT_FULL:
                $this->flashMessenger()->addErrorMessage("La partie est complète : libérez une place avant d'inscrire " . $name . '.');
                break;
            default:
                $this->flashMessenger()->addErrorMessage($name . " n'est plus en file d'attente.");
        }
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $register->getGame()->getIdGame()]);
    }

    /**
     * Applique les criteres de la file d'attente aux inscrits d'une partie.
     */
    public function generateQueueAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-games');
        }

        $game = $this->entityManager->getRepository(Game::class)->find(InputSanitizer::cleanInt($request->getPost('id')));
        if (!$game) {
            $this->flashMessenger()->addErrorMessage('Partie introuvable.');
            return $this->redirect()->toRoute('admin-games');
        }

        $queued = $this->gameManager->generateQueue($game);
        $this->flashMessenger()->addSuccessMessage(count($queued) . " joueur(s) placé(s) en file d'attente.");
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $game->getIdGame()]);
    }

    /**
     * Statistiques du site, reservees au GOD.
     */
    public function statsAction()
    {
        $currentUser = $this->authService->getIdentity();
        if (!$currentUser || !$currentUser->isGod()) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }

        $mail = $this->entityManager->getRepository(User::class)->countMailValidation();
        $mail['percent'] = $mail['total'] > 0 ? round($mail['validated'] * 100 / $mail['total'], 1) : 0;

        $view = new ViewModel([
            'mail' => $mail,
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-stats');
        $view->setTemplate('admin/stats');
        return $view;
    }

    public function nextGameAction()
    {

        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $currentUser = $this->authService->getIdentity();
        $request = $this->getRequest();

        $nextGame= $this->entityManager->getRepository(Game::class)->findNextGame();
        // Previously used simple findBy; keep it commented for reference:
        // $registers =  $this->entityManager->getRepository(GameRegister::class)->findBy(['game' => $nextGame, 'member'=>0, ],);
        // Use QueryBuilder to join the related user and order by user.firstname ASC
        $qb = $this->entityManager->getRepository(GameRegister::class)->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->where('r.game = :game')
            ->andWhere('r.member = 0')
            ->andWhere('r.status = :status')
            ->setParameter('game', $nextGame)
            ->setParameter('status', GameRegister::STATUS_ACTIVE)
            ->orderBy('u.firstname', 'ASC');
        $registers = $qb->getQuery()->getResult();
        if ($request->isPost()) {
            $data = InputSanitizer::cleanArray($this->params()->fromPost());
            $registerId = InputSanitizer::cleanInt($data['register_id'] ?? null);
            $action = InputSanitizer::cleanString($data['action'] ?? '');

            if ($registerId && in_array($action, ['validate', 'cancel'])) {
                $register = $this->entityManager->getRepository(GameRegister::class)->find($registerId);

                if ($register && $register->getStatus() === GameRegister::STATUS_ACTIVE) {
                    if ($action === 'validate') {
                        $nextArrived = $this->entityManager
                            ->getRepository(GameRegister::class)
                            ->getFirstMissingArrivedNumber($register, $nextGame->getIdgame());

                        $register->setPaid(1);
                        $register->setArrivedNumber($nextArrived ?: 0);
                    } else {
                        $register->setPaid(0);
                        $register->setArrivedNumber(0);
                    }

                    $this->entityManager->flush();
                }
            }

            return $this->redirect()->toRoute('admin-next-games');
        }

        $view = new ViewModel([
            'currentUser'=>$currentUser,
            'registers'=>$registers,
            'pendingQueue' => $nextGame ? $this->gameManager->getPendingQueue($nextGame) : [],
        ]);
        $this->layout()->setVariable('activeMenu', 'game');
        $view->setTemplate('admin/next-game');
        return $view;
    }



}
