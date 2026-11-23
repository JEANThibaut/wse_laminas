<?php
namespace Moderation\View\Helper;

use Annonce\Service\AnnonceManager;
use Moderation\Service\ReportService;
use User\Entity\User;

/**
 * Ce qui attend la moderation (GOD et moderateurs) : signalements en
 * attente et annonces a valider. Sert au bloc "A traiter" en tete de
 * l'accueil et aux compteurs du menu.
 * null pour tout autre compte : rien n'est affiche.
 * Usage : <?php $todo = $this->moderationTodo($this->currentUser); ?>
 */
class ModerationTodo
{
    private ReportService $reportService;
    private AnnonceManager $annonceManager;
    /** @var array<int, ?array> resultat par compte, calcule une fois par requete */
    private array $cache = [];

    public function __construct(ReportService $reportService, AnnonceManager $annonceManager)
    {
        $this->reportService = $reportService;
        $this->annonceManager = $annonceManager;
    }

    /**
     * @return array{reports: int, pending: int, total: int}|null
     */
    public function __invoke(?User $user): ?array
    {
        if (!$user || !$user->canModerate() || $user->isBlocked()) {
            return null;
        }
        $id = (int) $user->getIdUser();
        if (!array_key_exists($id, $this->cache)) {
            try {
                $reports = $this->reportService->pendingTargetCount();
                $pending = $this->annonceManager->countPending();
                $this->cache[$id] = ['reports' => $reports, 'pending' => $pending, 'total' => $reports + $pending];
            } catch (\Throwable $e) {
                // Tables absentes (migration pas encore jouee) : l'accueil et le menu ne doivent jamais casser
                $this->cache[$id] = ['reports' => 0, 'pending' => 0, 'total' => 0];
            }
        }
        return $this->cache[$id];
    }
}
