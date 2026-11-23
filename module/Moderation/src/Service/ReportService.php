<?php
namespace Moderation\Service;

use Annonce\Entity\Annonce;
use Annonce\Service\AnnonceSettings;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Messagerie\Entity\Conversation;
use Messagerie\Entity\Message;
use Moderation\Entity\Report;
use User\Entity\User;

/**
 * Signalements des membres. Une annonce signalee par N membres differents
 * (GOD MODE > Petites annonces > Reglages) est masquee en attendant la moderation.
 */
class ReportService
{
    // Anti-abus : signalements par membre et par jour
    private const DAILY_LIMIT = 20;

    private EntityManager $entityManager;
    private AnnonceSettings $settings;

    public function __construct(EntityManager $entityManager, AnnonceSettings $settings)
    {
        $this->entityManager = $entityManager;
        $this->settings = $settings;
    }

    /**
     * @return string|null erreur affichable, null si enregistre
     */
    public function reportAnnonce(User $reporter, Annonce $annonce, $reason, $comment): ?string
    {
        if ($annonce->isOwnedBy($reporter)) {
            return 'Vous ne pouvez pas signaler votre propre annonce.';
        }
        if (!$annonce->isOnline() && !$annonce->isHidden()) {
            return "Cette annonce n'est plus en ligne.";
        }
        if ($this->repository()->findOneBy(['reporter' => $reporter, 'annonce' => $annonce])) {
            return 'Vous avez déjà signalé cette annonce.';
        }
        if ($error = $this->checkLimit($reporter)) {
            return $error;
        }
        $this->entityManager->persist(Report::forAnnonce($reporter, $annonce, (string) $reason, $this->cleanComment($comment)));
        $this->entityManager->flush();

        if ($annonce->isOnline() && $this->countPending(['annonce' => $annonce]) >= $this->settings->getInt(AnnonceSettings::REPORT_THRESHOLD)) {
            $annonce->hide();
            $this->entityManager->flush();
        }
        return null;
    }

    /**
     * @return string|null erreur affichable, null si enregistre
     */
    public function reportMessage(User $reporter, Message $message, $reason, $comment): ?string
    {
        $conversation = $message->getConversation();
        if (!$conversation->participantFor($reporter) || $conversation->isAdmin() || $message->isFrom($reporter)) {
            return 'Ce message ne peut pas être signalé.';
        }
        if ($this->repository()->findOneBy(['reporter' => $reporter, 'message' => $message])) {
            return 'Vous avez déjà signalé ce message.';
        }
        if ($error = $this->checkLimit($reporter)) {
            return $error;
        }
        $this->entityManager->persist(Report::forMessage($reporter, $message, (string) $reason, $this->cleanComment($comment)));
        $this->entityManager->flush();
        return null;
    }

    /**
     * Nombre de cibles (annonces ou messages) avec des signalements en attente.
     */
    public function pendingTargetCount(): int
    {
        return (int) $this->entityManager->getConnection()->executeQuery(
            "SELECT COUNT(DISTINCT COALESCE(CONCAT('a', annonce_id), CONCAT('m', message_id)))
             FROM report WHERE status = :pending",
            ['pending' => Report::STATUS_PENDING]
        )->fetchOne();
    }

    /**
     * Signalements en attente regroupes par cible, la plus signalee d'abord.
     *
     * @return array<string, array{key: string, annonce: ?Annonce, message: ?Message, reports: Report[]}>
     */
    public function pendingQueue(): array
    {
        $groups = [];
        foreach ($this->repository()->findBy(['status' => Report::STATUS_PENDING], ['createdAt' => 'ASC']) as $report) {
            $key = $report->getAnnonce() ? 'annonce-' . $report->getAnnonce()->getId() : 'message-' . $report->getMessage()->getId();
            $groups[$key] ??= ['key' => $key, 'annonce' => $report->getAnnonce(), 'message' => $report->getMessage(), 'reports' => []];
            $groups[$key]['reports'][] = $report;
        }
        uasort($groups, fn ($a, $b) => count($b['reports']) <=> count($a['reports']));
        return $groups;
    }

    /**
     * @return Report[] signalements en attente d'une cible
     */
    public function pendingFor(?Annonce $annonce, ?Message $message): array
    {
        $criteria = ['status' => Report::STATUS_PENDING];
        if ($annonce) {
            $criteria['annonce'] = $annonce;
        } elseif ($message) {
            $criteria['message'] = $message;
        } else {
            return [];
        }
        return $this->repository()->findBy($criteria);
    }

    /**
     * Une conversation n'est lisible par la moderation que si un de ses messages a ete signale (CGU art. 8).
     */
    public function conversationIsReported(Conversation $conversation): bool
    {
        return (int) $this->entityManager->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM report r INNER JOIN message m ON m.id = r.message_id WHERE m.conversation_id = :conversation',
            ['conversation' => $conversation->getId()]
        )->fetchOne() > 0;
    }

    private function countPending(array $criteria): int
    {
        return count($this->repository()->findBy($criteria + ['status' => Report::STATUS_PENDING]));
    }

    private function checkLimit(User $reporter): ?string
    {
        $count = (int) $this->entityManager->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM report WHERE reporter_id = :reporter AND created_at > :since',
            ['reporter' => $reporter->getIdUser(), 'since' => (new \DateTime('-1 day'))->format('Y-m-d H:i:s')]
        )->fetchOne();
        return $count >= self::DAILY_LIMIT ? 'Trop de signalements aujourd’hui : réessayez demain.' : null;
    }

    private function cleanComment($comment): ?string
    {
        $comment = InputSanitizer::cleanText($comment);
        if ($comment === '' || !mb_check_encoding($comment, 'UTF-8')) {
            return null;
        }
        return mb_substr($comment, 0, Report::COMMENT_MAX);
    }

    private function repository()
    {
        return $this->entityManager->getRepository(Report::class);
    }
}
