<?php
namespace Moderation\View\Helper;

use Moderation\Service\ReportService;
use User\Entity\User;

/**
 * Badge du menu GOD MODE > Signalements : nombre d'annonces et de messages
 * en attente de decision. 0 pour tout autre compte que le GOD.
 */
class PendingReports
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function __invoke(?User $user): int
    {
        if (!$user || !$user->isGod()) {
            return 0;
        }
        try {
            return $this->reportService->pendingTargetCount();
        } catch (\Throwable $e) {
            // Table absente (migration pas encore jouee) : le menu ne doit jamais casser
            return 0;
        }
    }
}
