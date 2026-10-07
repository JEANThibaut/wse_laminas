<?php

namespace Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use User\Entity\LoginLog;
use Application\Entity\FaqItem;
use User\Entity\User;
use Application\Util\InputSanitizer;
use Game\Service\GameManager;
use Application\Service\PushService;
use Application\Service\FacebookPublisher;
use Game\Entity\QueueEntry;
use Game\Service\QueueManager;

class AdminController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $gameManager;
    private PushService $pushService;
    private QueueManager $queueManager;
    private FacebookPublisher $facebookPublisher;

    public function __construct($entityManager, $authService, $gameManager, PushService $pushService, QueueManager $queueManager, FacebookPublisher $facebookPublisher)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->gameManager = $gameManager;
        $this->pushService = $pushService;
        $this->queueManager = $queueManager;
        $this->facebookPublisher = $facebookPublisher;
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


    /**
     * Publication sur la Page Facebook, reservee au GOD : message prerempli avec
     * la prochaine partie, modifiable. En mode test (facebook.live = false), le
     * bouton cree un brouillon (publication non publiee, visible des seuls
     * admins de la Page).
     */
    public function publicationAction()
    {
        $currentUser = $this->authService->getIdentity();
        if (!$currentUser || !$currentUser->isGod()) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }

        $nextGame = $this->entityManager->getRepository(Game::class)->findNextGame();
        $render = fn (string $text) => $nextGame
            ? $this->facebookPublisher->renderMessage($text, $nextGame->getDate(), (int) $nextGame->getPlayerMax())
            : $text;

        $request = $this->getRequest();
        if ($request->isPost() && $this->facebookPublisher->canPublish($currentUser)) {
            $message = $render(InputSanitizer::cleanText($request->getPost('message')));
            if ($message === '') {
                $this->flashMessenger()->addErrorMessage("Le message est vide : rien n'a été envoyé.");
                return $this->redirect()->toRoute('admin-publication');
            }
            $this->publishOnFacebook($message);
            return $this->redirect()->toRoute('admin-publication');
        }

        $view = new ViewModel([
            'configured' => $this->facebookPublisher->isConfigured(),
            'live' => $this->facebookPublisher->isLive(),
            'nextGame' => $nextGame,
            'message' => $render($this->facebookPublisher->getTemplate()),
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-publication');
        $view->setTemplate('admin/publication');
        return $view;
    }

    /**
     * Publie sur la Page Facebook (brouillon en mode test) et rend compte du
     * resultat : lien vers la publication, ou erreur de Facebook.
     */
    private function publishOnFacebook(string $message): void
    {
        $result = $this->facebookPublisher->publish($message);
        if ($result['success']) {
            $this->flashMessenger()->addSuccessMessage(
                ($result['published']
                    ? 'Publié sur la Page Facebook : '
                    : 'Brouillon créé sur la Page Facebook (à retrouver dans les brouillons de Business Suite) : ')
                . $result['url']
            );
        } else {
            $this->flashMessenger()->addErrorMessage('Facebook : ' . $result['error']);
        }
    }

    /**
     * GOD MODE > FAQ : liste des questions (ordre d'affichage) et ajout.
     */
    public function faqAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            [$question, $answer] = $this->readFaqForm();
            if ($question === '' || $answer === '') {
                $this->flashMessenger()->addErrorMessage('La question et la réponse sont obligatoires.');
                return $this->redirect()->toRoute('admin-faq');
            }
            $items = $this->findFaqItems();
            $last = end($items);
            $this->entityManager->persist(new FaqItem($question, $answer, $last ? $last->getPosition() + 1 : 1));
            $this->entityManager->flush();
            $this->flashMessenger()->addSuccessMessage('Question ajoutée à la FAQ.');
            return $this->redirect()->toRoute('admin-faq');
        }

        $view = new ViewModel(['items' => $this->findFaqItems()]);
        $this->layout()->setVariable('activeMenu', 'admin-faq');
        $view->setTemplate('admin/faq');
        return $view;
    }

    /**
     * Modification d'une question : texte, reponse, visible ou masquee.
     */
    public function faqEditAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $item = $this->entityManager->getRepository(FaqItem::class)->find(InputSanitizer::cleanInt($this->params()->fromRoute('id')));
        if (!$item) {
            $this->flashMessenger()->addErrorMessage('Question introuvable.');
            return $this->redirect()->toRoute('admin-faq');
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            [$question, $answer] = $this->readFaqForm();
            if ($question === '' || $answer === '') {
                $this->flashMessenger()->addErrorMessage('La question et la réponse sont obligatoires.');
                return $this->redirect()->toRoute('admin-faq-edit', ['id' => $item->getId()]);
            }
            $item->update($question, $answer, (bool) $request->getPost('is_active'));
            $this->entityManager->flush();
            $this->flashMessenger()->addSuccessMessage('Question enregistrée.');
            return $this->redirect()->toRoute('admin-faq');
        }

        $view = new ViewModel(['item' => $item]);
        $this->layout()->setVariable('activeMenu', 'admin-faq');
        $view->setTemplate('admin/faq-edit');
        return $view;
    }

    /**
     * Monte ou descend une question d'un cran.
     */
    public function faqMoveAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $items = $this->findFaqItems();
            $id = InputSanitizer::cleanInt($request->getPost('id'));
            $offset = $request->getPost('direction') === 'up' ? -1 : 1;
            foreach ($items as $index => $item) {
                if ($item->getId() === $id && isset($items[$index + $offset])) {
                    [$items[$index], $items[$index + $offset]] = [$items[$index + $offset], $items[$index]];
                    break;
                }
            }
            // Positions renumerotees 1, 2, 3... dans le nouvel ordre
            foreach (array_values($items) as $index => $item) {
                $item->setPosition($index + 1);
            }
            $this->entityManager->flush();
        }
        return $this->redirect()->toRoute('admin-faq');
    }

    public function faqDeleteAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $item = $this->entityManager->getRepository(FaqItem::class)->find(InputSanitizer::cleanInt($request->getPost('id')));
            if ($item) {
                $this->entityManager->remove($item);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('Question supprimée de la FAQ.');
            }
        }
        return $this->redirect()->toRoute('admin-faq');
    }

    /**
     * @return FaqItem[] toutes les questions, visibles ou masquees, dans l'ordre d'affichage
     */
    private function findFaqItems(): array
    {
        return $this->entityManager->getRepository(FaqItem::class)->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /**
     * Question nettoyee comme une saisie ordinaire ; reponse conservee telle
     * quelle (HTML autorise : seul le GOD ecrit dans la FAQ).
     *
     * @return array{0: string, 1: string}
     */
    private function readFaqForm(): array
    {
        $request = $this->getRequest();
        $answer = str_replace(["\r\n", "\r"], "\n", (string) $request->getPost('answer', ''));

        return [
            mb_substr(InputSanitizer::cleanString($request->getPost('question')), 0, 255),
            trim($answer),
        ];
    }

    /**
     * Redirection si le compte connecte n'est pas GOD, sinon null.
     */
    private function requireGod()
    {
        $currentUser = $this->authService->getIdentity();
        if ($currentUser && $currentUser->isGod()) {
            return null;
        }
        $this->flashMessenger()->addErrorMessage('Accès refusé.');
        return $this->redirect()->toRoute('home');
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
            'status' => GameRegister::PLACE_STATUSES,
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
                        // Maximum augmente : les nouvelles places vont d'abord a la file
                        $this->flashOffers($this->queueManager->fillFreePlaces($game));
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
            'queue' => $this->queueManager->getQueue($game),
            'freePlaces' => $this->gameManager->countFreePlaces($game),
            'autoOffer' => $this->queueManager->isAutoOffer(),
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
            'status' => GameRegister::PLACE_STATUSES,
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
        $this->flashOffers($this->queueManager->fillFreePlaces($register->getGame()));

        return $this->redirect()->toRoute('admin-edit-game', ['id' => $register->getGame()->getIdGame()]);
    }

    /**
     * Passe un inscrit en fin de file d'attente (preinscription), sans le prevenir.
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
            'status' => GameRegister::PLACE_STATUSES,
        ]);
        if (!$register) {
            $this->flashMessenger()->addErrorMessage('Inscription introuvable.');
            return $this->redirect()->toRoute('admin-games');
        }

        $offers = $this->queueManager->moveToQueue($register);
        $user = $register->getUser();
        $this->flashMessenger()->addSuccessMessage(
            $user->getFirstname() . ' ' . $user->getLastname() . " a été placé en fin de file d'attente."
        );
        if ((int) $register->getPaid() === 1) {
            $this->flashMessenger()->addWarningMessage("Cette inscription était payée : le remboursement éventuel est à faire manuellement.");
        }
        $this->flashOffers($offers);

        return $this->redirect()->toRoute('admin-edit-game', ['id' => $register->getGame()->getIdGame()]);
    }

    /**
     * Inscription manuelle d'un joueur par un admin, meme si la partie est
     * complete. Un joueur en file d'attente y est directement inscrit et sort
     * de la file.
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
                $this->queueManager->onDirectRegistration($game, $user);
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
        $this->flashOffers($this->queueManager->fillFreePlaces($game));
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $game->getIdGame()]);
    }

    /**
     * Retire un joueur de la file d'attente. Si une place lui etait proposee,
     * elle passe au suivant.
     */
    public function queueRemoveAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-games');
        }
        $entry = $this->entityManager->getRepository(QueueEntry::class)->find(InputSanitizer::cleanInt($request->getPost('id')));
        if (!$entry) {
            $this->flashMessenger()->addErrorMessage("Joueur introuvable dans la file d'attente.");
            return $this->redirect()->toRoute('admin-games');
        }
        $offered = $this->queueManager->removeByAdmin($entry);
        $user = $entry->getUser();
        $this->flashMessenger()->addSuccessMessage(
            $user->getFirstname() . ' ' . $user->getLastname() . " a été retiré de la file d'attente."
        );
        $this->flashOffers($offered);
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $entry->getGame()->getIdGame()]);
    }

    /**
     * Mode manuel : propose une place libre a un joueur de la file (meme delai
     * et memes notifications qu'en mode automatique).
     */
    public function queueOfferAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-games');
        }
        $entry = $this->entityManager->getRepository(QueueEntry::class)->find(InputSanitizer::cleanInt($request->getPost('id')));
        if (!$entry) {
            $this->flashMessenger()->addErrorMessage("Joueur introuvable dans la file d'attente.");
            return $this->redirect()->toRoute('admin-games');
        }
        $name = $entry->getUser()->getFirstname() . ' ' . $entry->getUser()->getLastname();
        switch ($this->queueManager->offerTo($entry)) {
            case QueueManager::RESULT_OFFERED:
                $this->flashOffers([$entry]);
                break;
            case QueueManager::RESULT_NO_PLACE:
                $this->flashMessenger()->addErrorMessage("Aucune place libre à proposer : libérez une place ou augmentez le maximum.");
                break;
            case QueueManager::RESULT_NOT_WAITING:
                $this->flashMessenger()->addErrorMessage($name . " n'est plus en attente.");
                break;
            default:
                $this->flashMessenger()->addErrorMessage("La file d'attente de cette partie est fermée (partie inactive ou commencée).");
        }
        return $this->redirect()->toRoute('admin-edit-game', ['id' => $entry->getGame()->getIdGame()]);
    }

    /**
     * Signale les places que l'action vient de proposer a la file d'attente.
     *
     * @param QueueEntry[] $offered
     */
    private function flashOffers(array $offered): void
    {
        foreach ($offered as $entry) {
            $this->flashMessenger()->addMessage(
                'Place proposée à ' . $entry->getUser()->getFirstname() . ' ' . $entry->getUser()->getLastname()
                . " (file d'attente), prévenu par email."
            );
        }
    }

    /**
     * GOD MODE > Tableau de bord (PC uniquement) : les ecrans d'administration
     * affiches cote a cote, chacun dans une colonne au format telephone.
     */
    public function dashboardAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }

        // Ecrans proposes dans chaque colonne : cle => [libelle, route]
        $screens = [];
        foreach ([
            'games' => ['Les parties', 'admin-games'],
            'next-game' => ['Prochaine partie', 'admin-next-games'],
            'users' => ['Users', 'admin-users'],
            'actus' => ['Les actualités', 'actus-admin'],
            'stats' => ['Stats', 'admin-stats'],
            'logs' => ['Connexions', 'admin-logs'],
            'notifications' => ['Notifications', 'admin-notifications'],
            'publication' => ['Publication', 'admin-publication'],
            'faq' => ['FAQ', 'admin-faq'],
        ] as $key => [$label, $route]) {
            $screens[] = ['key' => $key, 'label' => $label, 'url' => $this->url()->fromRoute($route)];
        }

        $view = new ViewModel(['screens' => $screens]);
        $this->layout()->setVariable('activeMenu', 'admin-dashboard');
        $this->layout()->setVariable('disablePtr', true);
        $view->setTemplate('admin/dashboard');
        return $view;
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
            'preferenceColumns' => $this->pushService->hasPreferenceColumns(),
            'myPreferences' => $this->pushService->getPreferences($currentUser),
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
                if (!$nextGame) {
                    return [];
                }
                $registers = $this->entityManager->getRepository(GameRegister::class)->findBy([
                    'game' => $nextGame,
                    'status' => GameRegister::PLACE_STATUSES,
                ]);
                return array_map(fn (GameRegister $register) => $register->getUser(), $registers);
            case 'queue':
                return $nextGame
                    ? array_map(fn (QueueEntry $entry) => $entry->getUser(), $this->queueManager->getQueue($nextGame))
                    : [];
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
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('game', $nextGame)
            ->setParameter('statuses', GameRegister::PLACE_STATUSES)
            ->orderBy('u.firstname', 'ASC');
        $registers = $qb->getQuery()->getResult();
        if ($request->isPost()) {
            $data = InputSanitizer::cleanArray($this->params()->fromPost());
            $registerId = InputSanitizer::cleanInt($data['register_id'] ?? null);
            $action = InputSanitizer::cleanString($data['action'] ?? '');

            if ($registerId && in_array($action, ['validate', 'cancel'])) {
                $register = $this->entityManager->getRepository(GameRegister::class)->find($registerId);

                // Pointage : le joueur est arrive et a paye
                if ($register && $action === 'validate' && $register->isActive()) {
                    $nextArrived = $this->entityManager
                        ->getRepository(GameRegister::class)
                        ->getFirstMissingArrivedNumber($register, $nextGame->getIdgame());

                    $register->setStatus(GameRegister::STATUS_VALIDATED);
                    $register->setArrivedNumber($nextArrived ?: 0);
                    $this->entityManager->flush();
                } elseif ($register && $action === 'cancel' && $register->isValidated()) {
                    $register->setStatus(GameRegister::STATUS_ACTIVE);
                    $register->setArrivedNumber(0);
                    $this->entityManager->flush();
                }
            }

            return $this->redirect()->toRoute('admin-next-games');
        }

        $view = new ViewModel([
            'currentUser'=>$currentUser,
            'registers'=>$registers,
            'queue' => $nextGame ? $this->queueManager->getQueue($nextGame) : [],
        ]);
        $this->layout()->setVariable('activeMenu', 'game');
        $view->setTemplate('admin/next-game');
        return $view;
    }



}
