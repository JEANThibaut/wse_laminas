<?php
namespace Messagerie\Service;

use Application\Service\FeatureAccess;
use User\Entity\User;

/**
 * Qui a acces a la messagerie : fonctionnalite "messagerie" ouverte a son
 * niveau (config features : GOD seulement tant qu'elle est en test), compte
 * valide (email confirme), ni bloque ni desactive. Le GOD passe toujours.
 */
class MessagerieAccess
{
    public const FEATURE = 'messagerie';

    private FeatureAccess $featureAccess;

    public function __construct(FeatureAccess $featureAccess)
    {
        $this->featureAccess = $featureAccess;
    }

    public function canAccess(?User $user): bool
    {
        if (!$user || !$this->featureAccess->isAllowed(self::FEATURE, $user)) {
            return false;
        }
        if ($user->isGod()) {
            return true;
        }
        return $user->isMailValidated() && !$user->isBlocked() && !$user->isDeactivated();
    }
}
