<?php

namespace Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use User\Entity\LoginLog;
use User\Entity\User;
use Application\Util\InputSanitizer;
use Game\Service\GameManager;
use Application\Service\PushService;

class AdminController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $gameManager;
    private PushService $pushService;

    public function __construct($entityManager, $authService, $gameManager, PushService $pushService)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->gameManager = $gameManager;
        $this->pushService = $pushService;
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
            'addablePlayers' => $this->entityManager->getRepository(User::class)->findAddableToGame($game),
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
     * Inscription manuelle d'un joueur par un admin, meme si la partie est
     * complete. Un joueur en file d'attente y est directement inscrit.
     */
    public function addPlayerAction()
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
        $user = $this->entityManager->getRepository(User::class)->find(InputSanitizer::cleanInt($request->getPost('user_id')));
        if (!$user) {
            $this->flashMessenger()->addErrorMessage('Joueur introuvable.');
            return $this->redirect()->toRoute('admin-edit-game', ['id' => $game->getIdGame()]);
        }

        $name = $user->getFirstname() . ' ' . $user->getLastname();
        switch ($this->gameManager->adminAddPlayer($game, $user)) {
            case GameManager::RESULT_REGISTERED:
            case GameManager::RESULT_CONFIRMED:
                $this->flashMessenger()->addSuccessMessage($name . ' est inscrit à la partie.');
                $this->warnIfOverQuota($game);
                if (!$user->isMailValidated()) {
                    $this->flashMessenger()->addWarningMessage($name . " n'a pas validé son adresse email.");
                }
                break;
            default:
                $this->flashMessenger()->addErrorMessage($name . ' est déjà inscrit à cette partie.');
        }
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $game->getIdGame()]);
    }

    /**
     * Les inscriptions par un admin ignorent la limite de places : le signaler
     * quand elle est depassee.
     */
    private function warnIfOverQuota(Game $game): void
    {
        $count = $this->entityManager->getRepository(GameRegister::class)->countActiveRegisters($game);
        if ($count > $game->getPlayerMax()) {
            $this->flashMessenger()->addWarningMessage(
                'La partie dépasse le nombre maximum de joueurs : ' . $count . '/' . $game->getPlayerMax() . '.'
            );
        }
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
                $this->warnIfOverQuota($register->getGame());
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

    // Destinataires possibles d'une notification
    private const PUSH_TARGETS = [
        'me' => 'Moi uniquement',
        'player' => 'Un joueur',
        'registered' => 'Inscrits à la prochaine partie',
        'queue' => "File d'attente de la prochaine partie",
        'all' => 'Tous les joueurs actifs',
    ];

    /**
     * Envoi d'une notification aux appareils abonnes, reserve au GOD. En
     * phase de test, PushService ecarte tout compte non autorise.
     */
    public function notificationsAction()
    {
        $currentUser = $this->authService->getIdentity();
        if (!$currentUser || !$currentUser->isGod()) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }

        $nextGame = $this->entityManager->getRepository(Game::class)->findNextGame();
        $request = $this->getRequest();

        if ($request->isPost() && $this->pushService->isConfigured()) {
            $data = InputSanitizer::cleanArray($request->getPost()->toArray());
            $title = trim($data['title'] ?? '');
            $body = trim($data['body'] ?? '');
            $target = $data['target'] ?? '';
            $category = $data['category'] ?? '';
            $url = trim($data['url'] ?? '') ?: '/';

            if ($title === '' || $body === '' || !array_key_exists($target, self::PUSH_TARGETS)
                || !array_key_exists($category, PushService::CATEGORY_LABELS)) {
                $this->flashMessenger()->addErrorMessage('Type, titre, message et destinataires sont obligatoires.');
                return $this->redirect()->toRoute('admin-notifications');
            }

            $recipients = $this->findPushRecipients($target, $currentUser, $nextGame, InputSanitizer::cleanInt($data['user_id'] ?? 0));
            $report = $this->pushService->send($recipients, $category, $title, $body, $url);

            if ($report['devices'] === 0) {
                $this->flashMessenger()->addWarningMessage("Aucun appareil abonné parmi les destinataires : rien n'a été envoyé.");
            } else {
                $this->flashMessenger()->addSuccessMessage(
                    'Notification envoyée à ' . $report['sent'] . '/' . $report['devices'] . ' appareil(s), '
                    . $report['recipients'] . ' joueur(s).'
                );
            }
            if ($report['skipped'] > 0) {
                $this->flashMessenger()->addMessage($report['skipped'] . ' joueur(s) écarté(s) : mode test, seuls les comptes GOD reçoivent.');
            }
            if ($report['optedOut'] > 0) {
                $this->flashMessenger()->addMessage(
                    $report['optedOut'] . ' joueur(s) ont coupé les notifications « ' . PushService::CATEGORY_LABELS[$category] . ' » dans leur profil.'
                );
            }
            if ($report['removed'] > 0) {
                $this->flashMessenger()->addMessage($report['removed'] . ' abonnement(s) expiré(s) supprimé(s).');
            }
            if ($report['errors']) {
                $this->flashMessenger()->addErrorMessage('Échecs : ' . implode(' | ', array_unique($report['errors'])));
            }
            return $this->redirect()->toRoute('admin-notifications');
        }

        $view = new ViewModel([
            'configured' => $this->pushService->isConfigured(),
            'targets' => self::PUSH_TARGETS,
            'categories' => PushService::CATEGORY_LABELS,
            'players' => $this->findActivePlayers(),
            'nextGame' => $nextGame,
            'mySubscriptions' => $this->pushService->isConfigured()
                ? count($this->pushService->findSubscriptions([$currentUser])[$currentUser->getIdUser()] ?? [])
                : 0,
            'titleMax' => PushService::TITLE_MAX,
            'bodyMax' => PushService::BODY_MAX,
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-notifications');
        $view->setTemplate('admin/notifications');
        return $view;
    }

    /**
     * @return User[]
     */
    private function findPushRecipients(string $target, User $currentUser, ?Game $nextGame, int $userId): array
    {
        switch ($target) {
            case 'me':
                return [$currentUser];
            case 'player':
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                return $user ? [$user] : [];
            case 'registered':
            case 'queue':
                if (!$nextGame) {
                    return [];
                }
                $registers = $this->entityManager->getRepository(GameRegister::class)->findBy([
                    'game' => $nextGame,
                    'status' => $target === 'queue' ? GameRegister::STATUS_PENDING : GameRegister::STATUS_ACTIVE,
                ]);
                return array_map(fn (GameRegister $register) => $register->getUser(), $registers);
            case 'all':
                return $this->findActivePlayers();
        }
        return [];
    }

    /**
     * Comptes actifs, joueurs et membres, tries par nom.
     *
     * @return User[]
     */
    private function findActivePlayers(): array
    {
        $repository = $this->entityManager->getRepository(User::class);
        $players = array_merge($repository->search(''), $repository->search('', $repository::SCOPE_MEMBERS));
        usort($players, fn (User $a, User $b) => [mb_strtolower($a->getLastname()), mb_strtolower($a->getFirstname())]
            <=> [mb_strtolower($b->getLastname()), mb_strtolower($b->getFirstname())]);
        return $players;
    }

    /**
     * Journal des connexions et dernieres visites, reserve au GOD.
     */
    public function logsAction()
    {
        $currentUser = $this->authService->getIdentity();
        if (!$currentUser || !$currentUser->isGod()) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }

        $term = InputSanitizer::cleanString($this->params()->fromQuery('q', ''));
        $state = InputSanitizer::cleanString($this->params()->fromQuery('state', ''));
        if (!array_key_exists($state, LoginLog::STATE_LABELS)) {
            $state = '';
        }

        $logRepository = $this->entityManager->getRepository(LoginLog::class);
        $lastSeenUsers = $this->entityManager->getRepository(User::class)->findLastSeen();
        $dayAgo = new \DateTimeImmutable('-24 hours');

        $view = new ViewModel([
            'term' => $term,
            'state' => $state,
            'logs' => $logRepository->findLatest($term, $state),
            'lastSeenUsers' => $lastSeenUsers,
            'summary' => [
                'loginsToday' => $logRepository->countSince(
                    [LoginLog::STATE_SUCCESS, LoginLog::STATE_SIGNUP],
                    new \DateTimeImmutable('today')
                ),
                'failuresWeek' => $logRepository->countSince(
                    [LoginLog::STATE_WRONG_PASSWORD, LoginLog::STATE_UNKNOWN_EMAIL],
                    new \DateTimeImmutable('-7 days')
                ),
                'activeDay' => count(array_filter($lastSeenUsers, fn ($seen) => $seen['lastSeenAt'] >= $dayAgo)),
            ],
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-logs');
        $view->setTemplate('admin/logs');
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
