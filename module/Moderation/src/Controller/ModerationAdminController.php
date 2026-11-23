<?php
namespace Moderation\Controller;

use Annonce\Entity\Annonce;
use Annonce\Service\AnnonceManager;
use Application\Service\AuthService;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Messagerie\Entity\Conversation;
use Messagerie\Entity\Message;
use Moderation\Entity\Sanction;
use Moderation\Service\ModerationManager;
use Moderation\Service\ReportService;
use Moderation\Service\SanctionService;
use User\Entity\User;

/**
 * Moderation (GOD et moderateurs) : annonces a valider, signalements en
 * attente, decision, historique des sanctions, lecture d'une conversation
 * signalee. Le blocage de compte (et sa levee) reste reserve au GOD.
 */
class ModerationAdminController extends AbstractActionController
{
    private EntityManager $entityManager;
    private AuthService $authService;
    private ReportService $reportService;
    private SanctionService $sanctionService;
    private ModerationManager $moderation;
    private AnnonceManager $annonceManager;

    public function __construct(
        EntityManager $entityManager,
        AuthService $authService,
        ReportService $reportService,
        SanctionService $sanctionService,
        ModerationManager $moderation,
        AnnonceManager $annonceManager
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->reportService = $reportService;
        $this->sanctionService = $sanctionService;
        $this->moderation = $moderation;
        $this->annonceManager = $annonceManager;
    }

    public function queueAction()
    {
        if (!$moderator = $this->moderator()) {
            return $this->denied();
        }
        $queue = $this->reportService->pendingQueue();
        $authors = [];
        foreach ($queue as $key => $group) {
            $authors[$key] = $this->authorInfo($group['annonce'] ? $group['annonce']->getUser() : $group['message']->getSender());
        }
        $pending = $this->annonceManager->pendingAnnonces();
        $pendingAuthors = [];
        foreach ($pending as $annonce) {
            $pendingAuthors[$annonce->getId()] = $this->authorInfo($annonce->getUser());
        }
        return $this->page('moderation-admin/queue', [
            'queue' => $queue,
            'authors' => $authors,
            'pending' => $pending,
            'pendingAuthors' => $pendingAuthors,
            'suspensionDays' => $this->moderation->suspensionDays(),
            'canBlacklist' => $moderator->isGod(),
        ]);
    }

    public function decideAction()
    {
        if (!$moderator = $this->moderator()) {
            return $this->denied();
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            [$type, $id] = array_pad(explode('-', (string) $request->getPost('target'), 2), 2, 0);
            $annonce = $type === 'annonce' ? $this->entityManager->getRepository(Annonce::class)->find((int) $id) : null;
            $message = $type === 'message' ? $this->entityManager->getRepository(Message::class)->find((int) $id) : null;
            $sanction = (string) $request->getPost('sanction', ModerationManager::NO_SANCTION);
            if (!$annonce && !$message) {
                $this->flashMessenger()->addErrorMessage('Cible introuvable.');
            } elseif ($sanction === Sanction::TYPE_BLACKLIST && !$moderator->isGod()) {
                $this->flashMessenger()->addErrorMessage('Le blocage de compte est réservé au GOD.');
            } else {
                $summary = $this->moderation->decide(
                    $moderator,
                    $annonce,
                    $message,
                    $request->getPost('decision') === ModerationManager::DECISION_REMOVE ? ModerationManager::DECISION_REMOVE : ModerationManager::DECISION_DISMISS,
                    (string) $request->getPost('motive'),
                    $sanction,
                    InputSanitizer::cleanInt($request->getPost('days'))
                );
                $this->flashMessenger()->addSuccessMessage($summary);
            }
        }
        return $this->redirect()->toRoute('admin-moderation');
    }

    /**
     * Validation d'une annonce en attente : elle passe en ligne.
     */
    public function approveAction()
    {
        if (!$moderator = $this->moderator()) {
            return $this->denied();
        }
        $request = $this->getRequest();
        $annonce = $request->isPost() ? $this->entityManager->getRepository(Annonce::class)->find(InputSanitizer::cleanInt($request->getPost('id'))) : null;
        if ($annonce && $this->annonceManager->approve($annonce, $moderator)) {
            $this->flashMessenger()->addSuccessMessage('Annonce validée : elle est en ligne.');
        } else {
            $this->flashMessenger()->addErrorMessage('Annonce introuvable ou déjà traitée.');
        }
        return $this->redirect()->toRoute('admin-moderation');
    }

    public function sanctionsAction()
    {
        if (!$moderator = $this->moderator()) {
            return $this->denied();
        }
        return $this->page('moderation-admin/sanctions', [
            'sanctions' => $this->sanctionService->recent(),
            'canBlacklist' => $moderator->isGod(),
        ]);
    }

    public function revokeAction()
    {
        if (!$moderator = $this->moderator()) {
            return $this->denied();
        }
        $request = $this->getRequest();
        $sanction = $request->isPost() ? $this->entityManager->getRepository(Sanction::class)->find(InputSanitizer::cleanInt($request->getPost('id'))) : null;
        if ($sanction && $sanction->getType() === Sanction::TYPE_BLACKLIST && !$moderator->isGod()) {
            $this->flashMessenger()->addErrorMessage('Seul le GOD peut débloquer un compte.');
        } elseif ($sanction) {
            $this->sanctionService->revoke($sanction);
            $this->flashMessenger()->addSuccessMessage('Sanction levée.');
        }
        return $this->redirect()->toRoute('admin-moderation-sanctions');
    }

    /**
     * Lecture d'une conversation, seulement si un de ses messages a ete signale.
     */
    public function conversationAction()
    {
        if (!$this->moderator()) {
            return $this->denied();
        }
        $conversation = $this->entityManager->getRepository(Conversation::class)->find(InputSanitizer::cleanInt($this->params()->fromRoute('id')));
        if (!$conversation || !$this->reportService->conversationIsReported($conversation)) {
            $this->flashMessenger()->addErrorMessage('Seule une conversation signalée peut être consultée.');
            return $this->redirect()->toRoute('admin-moderation');
        }
        return $this->page('moderation-admin/conversation', [
            'conversation' => $conversation,
            'highlight' => InputSanitizer::cleanInt($this->params()->fromQuery('message')),
        ]);
    }

    private function authorInfo(?User $author): ?array
    {
        return $author ? [
            'user' => $author,
            'suggested' => $this->sanctionService->suggestedType($author),
            'history' => $this->sanctionService->history($author),
        ] : null;
    }

    /**
     * Compte connecte s'il peut moderer (GOD ou moderateur), sinon null.
     */
    private function moderator(): ?User
    {
        $user = $this->authService->getIdentity();
        return $user instanceof User && $user->canModerate() && !$user->isBlocked() ? $user : null;
    }

    private function denied()
    {
        $this->flashMessenger()->addErrorMessage('Accès refusé.');
        return $this->redirect()->toRoute('home');
    }

    private function page(string $template, array $variables): ViewModel
    {
        $this->layout()->setVariable('activeMenu', 'admin-moderation');
        $view = new ViewModel($variables);
        $view->setTemplate($template);
        return $view;
    }
}
