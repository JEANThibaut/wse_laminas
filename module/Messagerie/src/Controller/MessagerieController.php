<?php
namespace Messagerie\Controller;

use Annonce\Entity\Annonce;
use Application\Service\AuthService;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Messagerie\Entity\Conversation;
use Messagerie\Service\MessagerieAccess;
use Messagerie\Service\MessagerieManager;
use User\Entity\User;

/**
 * Messagerie interne, cote membres : boite de reception, conversation,
 * premier message a l'auteur d'une annonce, blocage, archivage.
 */
class MessagerieController extends AbstractActionController
{
    private EntityManager $entityManager;
    private AuthService $authService;
    private MessagerieAccess $access;
    private MessagerieManager $manager;

    public function __construct(EntityManager $entityManager, AuthService $authService, MessagerieAccess $access, MessagerieManager $manager)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->access = $access;
        $this->manager = $manager;
    }

    public function inboxAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        return $this->page('messagerie/inbox', [
            'participants' => $this->manager->conversations()->findInbox($user),
            'unreadIds' => $this->manager->conversations()->unreadConversationIds($user),
            'user' => $user,
        ]);
    }

    /**
     * Une conversation : lecture (marquee lue) et reponse.
     */
    public function conversationAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        $conversation = $this->entityManager->getRepository(Conversation::class)->find(InputSanitizer::cleanInt($this->params()->fromRoute('id')));
        $participant = $conversation ? $conversation->participantFor($user) : null;
        if (!$participant) {
            $this->flashMessenger()->addErrorMessage('Conversation introuvable.');
            return $this->redirect()->toRoute('messagerie');
        }

        $request = $this->getRequest();
        $draft = '';
        if ($request->isPost()) {
            $error = $this->manager->reply($conversation, $user, $request->getPost('body'));
            if (!$error) {
                return $this->redirect()->toRoute('messagerie-conversation', ['id' => $conversation->getId()], ['fragment' => 'fin']);
            }
            $this->flashMessenger()->addErrorMessage($error);
            $draft = InputSanitizer::cleanText($request->getPost('body'));
        }

        // Date de lecture avant ce passage : les messages arrives depuis sont mis en evidence
        $previousRead = $participant->getLastReadAt();
        $this->manager->markRead($participant);
        $other = $conversation->isAdmin() ? null : $conversation->otherUser($user);

        return $this->page('messagerie/conversation', [
            'conversation' => $conversation,
            'user' => $user,
            'other' => $other,
            'previousRead' => $previousRead,
            'iBlocked' => $other ? $this->manager->hasBlocked($user, $other) : false,
            'draft' => $draft,
        ]);
    }

    /**
     * Premier message a l'auteur d'une annonce ; s'il existe deja un fil
     * pour cette annonce, on y est renvoye.
     */
    public function newAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        $annonce = $this->entityManager->getRepository(Annonce::class)->find(InputSanitizer::cleanInt($this->params()->fromRoute('annonce')));
        if (!$annonce || !$annonce->isOnline()) {
            $this->flashMessenger()->addErrorMessage("Cette annonce n'est plus en ligne.");
            return $this->redirect()->toRoute('messagerie');
        }
        if ($annonce->isOwnedBy($user)) {
            return $this->redirect()->toRoute('annonces-view', ['id' => $annonce->getId()]);
        }
        $existing = $this->manager->conversations()->findForAnnonce($annonce, $user);
        if ($existing) {
            return $this->redirect()->toRoute('messagerie-conversation', ['id' => $existing->getId()], ['fragment' => 'fin']);
        }

        $request = $this->getRequest();
        $draft = '';
        if ($request->isPost()) {
            $result = $this->manager->contactAboutAnnonce($user, $annonce, $request->getPost('body'));
            if ($result['conversation']) {
                $this->flashMessenger()->addSuccessMessage('Message envoyé.');
                return $this->redirect()->toRoute('messagerie-conversation', ['id' => $result['conversation']->getId()], ['fragment' => 'fin']);
            }
            $this->flashMessenger()->addErrorMessage($result['error']);
            $draft = InputSanitizer::cleanText($request->getPost('body'));
        }

        return $this->page('messagerie/new', [
            'annonce' => $annonce,
            'draft' => $draft,
        ]);
    }

    /**
     * Bloquer / debloquer l'autre membre d'une conversation.
     */
    public function blockAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        $request = $this->getRequest();
        $conversation = $request->isPost()
            ? $this->entityManager->getRepository(Conversation::class)->find(InputSanitizer::cleanInt($request->getPost('id')))
            : null;
        if (!$conversation || !$conversation->participantFor($user) || $conversation->isAdmin()) {
            return $this->redirect()->toRoute('messagerie');
        }
        $other = $conversation->otherUser($user);
        if ($other) {
            $blocked = $this->manager->toggleBlock($user, $other);
            $this->flashMessenger()->addSuccessMessage($blocked
                ? 'Membre bloqué : il ne peut plus vous écrire.'
                : 'Membre débloqué.');
        }
        return $this->redirect()->toRoute('messagerie-conversation', ['id' => $conversation->getId()]);
    }

    /**
     * Retire le fil de la boite de reception (il revient au message suivant).
     */
    public function archiveAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $request = $this->getRequest();
        $conversation = $request->isPost()
            ? $this->entityManager->getRepository(Conversation::class)->find(InputSanitizer::cleanInt($request->getPost('id')))
            : null;
        $participant = $conversation ? $conversation->participantFor($this->currentUser()) : null;
        if ($participant) {
            $this->manager->archive($participant);
            $this->flashMessenger()->addSuccessMessage('Conversation archivée.');
        }
        return $this->redirect()->toRoute('messagerie');
    }

    private function guard()
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirect()->toRoute('login');
        }
        if (!$this->access->canAccess($user)) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }
        return null;
    }

    private function currentUser(): ?User
    {
        $user = $this->authService->getIdentity();
        return $user instanceof User ? $user : null;
    }

    private function page(string $template, array $variables): ViewModel
    {
        $this->layout()->setVariable('activeMenu', 'messagerie');
        $view = new ViewModel($variables);
        $view->setTemplate($template);
        return $view;
    }
}
