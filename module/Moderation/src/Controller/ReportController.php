<?php
namespace Moderation\Controller;

use Annonce\Entity\Annonce;
use Annonce\Service\AnnonceAccess;
use Application\Service\AuthService;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Messagerie\Entity\Message;
use Messagerie\Service\MessagerieAccess;
use Moderation\Service\ReportService;
use User\Entity\User;

/**
 * Signalement d'une annonce ou d'un message par un membre (POST depuis la
 * fenetre "Signaler"), puis retour a la page d'origine.
 */
class ReportController extends AbstractActionController
{
    private EntityManager $entityManager;
    private AuthService $authService;
    private AnnonceAccess $annonceAccess;
    private MessagerieAccess $messagerieAccess;
    private ReportService $reportService;

    public function __construct(
        EntityManager $entityManager,
        AuthService $authService,
        AnnonceAccess $annonceAccess,
        MessagerieAccess $messagerieAccess,
        ReportService $reportService
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->annonceAccess = $annonceAccess;
        $this->messagerieAccess = $messagerieAccess;
        $this->reportService = $reportService;
    }

    public function reportAction()
    {
        $user = $this->authService->getIdentity();
        $request = $this->getRequest();
        if (!$user instanceof User || !$request->isPost()) {
            return $this->redirect()->toRoute('home');
        }
        $id = InputSanitizer::cleanInt($request->getPost('id'));
        $reason = (string) $request->getPost('reason');
        $comment = $request->getPost('comment');

        if ($request->getPost('type') === 'message') {
            $message = $this->entityManager->getRepository(Message::class)->find($id);
            if (!$message || !$this->messagerieAccess->canAccess($user)) {
                return $this->redirect()->toRoute('messagerie');
            }
            $error = $this->reportService->reportMessage($user, $message, $reason, $comment);
            $this->feedback($error);
            return $this->redirect()->toRoute('messagerie-conversation', ['id' => $message->getConversation()->getId()]);
        }

        $annonce = $this->entityManager->getRepository(Annonce::class)->find($id);
        if (!$annonce || !$this->annonceAccess->canAccess($user)) {
            return $this->redirect()->toRoute('annonces');
        }
        $error = $this->reportService->reportAnnonce($user, $annonce, $reason, $comment);
        $this->feedback($error);
        return $this->redirect()->toRoute($annonce->isOnline() ? 'annonces-view' : 'annonces', ['id' => $annonce->getId()]);
    }

    private function feedback(?string $error): void
    {
        if ($error) {
            $this->flashMessenger()->addErrorMessage($error);
        } else {
            $this->flashMessenger()->addSuccessMessage('Signalement envoyé, merci. La modération va l’examiner.');
        }
    }
}
