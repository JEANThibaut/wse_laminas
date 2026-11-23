<?php
namespace Moderation\Controller;

use Annonce\Entity\Annonce;
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
 * GOD MODE > Signalements : file des signalements en attente, decision,
 * historique des sanctions, lecture d'une conversation signalee.
 */
class ModerationAdminController extends AbstractActionController
{
    private EntityManager $entityManager;
    private AuthService $authService;
    private ReportService $reportService;
    private SanctionService $sanctionService;
    private ModerationManager $moderation;

    public function __construct(
        EntityManager $entityManager,
        AuthService $authService,
        ReportService $reportService,
        SanctionService $sanctionService,
        ModerationManager $moderation
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->reportService = $reportService;
        $this->sanctionService = $sanctionService;
        $this->moderation = $moderation;
    }

    public function queueAction()
    {
        if (!$admin = $this->god()) {
            return $this->denied();
        }
        $queue = $this->reportService->pendingQueue();
        $authors = [];
        foreach ($queue as $key => $group) {
            $author = $group['annonce'] ? $group['annonce']->getUser() : $group['message']->getSender();
            $authors[$key] = $author ? [
                'user' => $author,
                'suggested' => $this->sanctionService->suggestedType($author),
                'history' => $this->sanctionService->history($author),
            ] : null;
        }
        return $this->page('moderation-admin/queue', [
            'queue' => $queue,
            'authors' => $authors,
            'suspensionDays' => $this->moderation->suspensionDays(),
        ]);
    }

    public function decideAction()
    {
        if (!$admin = $this->god()) {
            return $this->denied();
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            [$type, $id] = array_pad(explode('-', (string) $request->getPost('target'), 2), 2, 0);
            $annonce = $type === 'annonce' ? $this->entityManager->getRepository(Annonce::class)->find((int) $id) : null;
            $message = $type === 'message' ? $this->entityManager->getRepository(Message::class)->find((int) $id) : null;
            if (!$annonce && !$message) {
                $this->flashMessenger()->addErrorMessage('Cible introuvable.');
            } else {
                $summary = $this->moderation->decide(
                    $admin,
                    $annonce,
                    $message,
                    $request->getPost('decision') === ModerationManager::DECISION_REMOVE ? ModerationManager::DECISION_REMOVE : ModerationManager::DECISION_DISMISS,
                    (string) $request->getPost('motive'),
                    (string) $request->getPost('sanction', ModerationManager::NO_SANCTION),
                    InputSanitizer::cleanInt($request->getPost('days'))
                );
                $this->flashMessenger()->addSuccessMessage($summary);
            }
        }
        return $this->redirect()->toRoute('admin-moderation');
    }

    public function sanctionsAction()
    {
        if (!$this->god()) {
            return $this->denied();
        }
        return $this->page('moderation-admin/sanctions', ['sanctions' => $this->sanctionService->recent()]);
    }

    public function revokeAction()
    {
        if (!$this->god()) {
            return $this->denied();
        }
        $request = $this->getRequest();
        $sanction = $request->isPost() ? $this->entityManager->getRepository(Sanction::class)->find(InputSanitizer::cleanInt($request->getPost('id'))) : null;
        if ($sanction) {
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
        if (!$this->god()) {
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

    private function god(): ?User
    {
        $user = $this->authService->getIdentity();
        return $user instanceof User && $user->isGod() ? $user : null;
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
