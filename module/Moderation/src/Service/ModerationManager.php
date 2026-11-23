<?php
namespace Moderation\Service;

use Annonce\Entity\Annonce;
use Annonce\Service\AnnonceSettings;
use Doctrine\ORM\EntityManager;
use Messagerie\Entity\Message;
use Messagerie\Service\MessagerieManager;
use Moderation\Entity\Report;
use Moderation\Entity\Sanction;
use User\Entity\User;

/**
 * Decision de la moderation sur une cible signalee : classer sans suite ou
 * retirer, avec une sanction eventuelle de l'auteur. L'auteur est toujours
 * informe d'un retrait ou d'une sanction, par un message de l'administration
 * (CGU art. 10 : retrait motive).
 */
class ModerationManager
{
    public const DECISION_DISMISS = 'dismiss';
    public const DECISION_REMOVE = 'remove';
    public const NO_SANCTION = 'none';

    private EntityManager $entityManager;
    private ReportService $reportService;
    private SanctionService $sanctionService;
    private MessagerieManager $messagerie;
    private AnnonceSettings $settings;

    public function __construct(
        EntityManager $entityManager,
        ReportService $reportService,
        SanctionService $sanctionService,
        MessagerieManager $messagerie,
        AnnonceSettings $settings
    ) {
        $this->entityManager = $entityManager;
        $this->reportService = $reportService;
        $this->sanctionService = $sanctionService;
        $this->messagerie = $messagerie;
        $this->settings = $settings;
    }

    /**
     * @return string resume affichable de ce qui a ete fait
     */
    public function decide(User $admin, ?Annonce $annonce, ?Message $message, string $decision, string $motive, string $sanctionType, int $days): string
    {
        $reports = $this->reportService->pendingFor($annonce, $message);
        $author = $annonce ? $annonce->getUser() : ($message ? $message->getSender() : null);
        $motiveLabel = Report::REASONS[$motive] ?? Report::REASONS['other'];
        $done = [];

        if ($decision === self::DECISION_REMOVE) {
            if ($annonce) {
                $annonce->close(Annonce::STATUS_REMOVED);
                $done[] = 'annonce retirée';
            } elseif ($message) {
                $message->redact();
                $done[] = 'message retiré';
            }
        } elseif ($annonce) {
            // Classee sans suite : l'annonce masquee par les signalements revient
            $annonce->restore();
        }
        foreach ($reports as $report) {
            $report->handle($decision === self::DECISION_REMOVE ? Report::STATUS_ACTIONED : Report::STATUS_DISMISSED, $admin);
        }
        $this->entityManager->flush();
        if ($decision !== self::DECISION_REMOVE) {
            $done[] = 'signalements classés sans suite';
        }

        $sanction = null;
        if ($author && $sanctionType !== self::NO_SANCTION) {
            $what = $annonce ? 'annonce « ' . $annonce->getTitle() . ' »' : 'message';
            $sanction = $this->sanctionService->apply($author, $sanctionType, $motiveLabel . ' (' . $what . ')', $days, $admin);
            if ($sanction) {
                $done[] = mb_strtolower($sanction->getTypeLabel());
            }
        }

        // Un compte bloque ne peut plus se connecter : inutile de lui ecrire
        if ($author && ($decision === self::DECISION_REMOVE || $sanction) && !$author->isGod()
            && !($sanction && $sanction->getType() === Sanction::TYPE_BLACKLIST)) {
            $this->notify($admin, $author, $annonce, $message, $decision === self::DECISION_REMOVE, $motiveLabel, $sanction);
            $done[] = 'auteur informé';
        }

        return ucfirst(implode(', ', $done)) . '.';
    }

    private function notify(User $admin, User $author, ?Annonce $annonce, ?Message $message, bool $removed, string $motiveLabel, ?Sanction $sanction): void
    {
        $lines = [];
        if ($removed) {
            $lines[] = $annonce
                ? 'Votre annonce « ' . $annonce->getTitle() . ' » a été retirée par la modération.'
                : 'Un de vos messages a été retiré par la modération.';
        } else {
            $lines[] = $annonce
                ? 'Votre annonce « ' . $annonce->getTitle() . ' » a été examinée par la modération.'
                : 'Un de vos messages a été examiné par la modération.';
        }
        $lines[] = 'Motif : ' . $motiveLabel . '.';
        if ($sanction) {
            switch ($sanction->getType()) {
                case Sanction::TYPE_WARNING:
                    $lines[] = 'Ceci est un avertissement. En cas de récidive, vous pourrez être interdit de publier des annonces.';
                    break;
                case Sanction::TYPE_SUSPENSION:
                    $lines[] = 'Vous ne pouvez plus publier d’annonces jusqu’au ' . $sanction->getEndsAt()->format('d/m/Y') . '.';
                    break;
                case Sanction::TYPE_BAN:
                    $lines[] = 'Vous ne pouvez plus publier d’annonces.';
                    break;
            }
        }
        $lines[] = 'Rappel : les règles sont dans les conditions d’utilisation des Petites annonces.';

        $subject = $removed ? ($annonce ? 'Modération : annonce retirée' : 'Modération : message retiré') : 'Modération : avertissement';
        $this->messagerie->broadcast($admin, [$author], $subject, implode("\n\n", $lines));
    }

    public function suspensionDays(): int
    {
        return $this->settings->getInt(AnnonceSettings::SUSPENSION_DAYS);
    }
}
