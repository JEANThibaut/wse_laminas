<?php

namespace Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use User\Entity\User;
use Application\Util\InputSanitizer;

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
            'players'=>$players
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
            ->andWhere('r.paid = 1')
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

                if ($register) {
                    // $nextArrived = $this->entityManager->getRepository(GameRegister::class)->getNextArrivedNumber($register,$nextGame->getIdgame());
                    $nextArrived = $this->entityManager->getRepository(GameRegister::class)->getFirstMissingArrivedNumber($register,$nextGame->getIdgame());

                    if($register->getArrivedNumber() == 0){

                    }
    
                    if($nextArrived){
                        $register->setArrivedNumber($action === 'validate' ? $nextArrived : 0);
                    }
                    else{
                        $register->setArrivedNumber($action === 'validate' ? 0 : 0);
                    }
                    $this->entityManager->flush();
                }
            }

            return $this->redirect()->toRoute('admin-next-games');
        }

        $view = new ViewModel([
            'currentUser'=>$currentUser,
            'registers'=>$registers
        ]);
        $this->layout()->setVariable('activeMenu', 'game');
        $view->setTemplate('admin/next-game');
        return $view;
    }



}
