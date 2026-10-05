<?php
namespace Application\View\Helper;

use Application\Service\PushService;
use User\Entity\User;

/**
 * Cle publique VAPID si le compte peut activer les notifications, sinon ''.
 * Usage : <?php if ($key = $this->pushPublicKey($this->currentUser)): ?>
 */
class PushPublicKey
{
    private PushService $pushService;

    public function __construct(PushService $pushService)
    {
        $this->pushService = $pushService;
    }

    public function __invoke(?User $user): string
    {
        return $this->pushService->canUse($user) ? $this->pushService->getPublicKey() : '';
    }
}
