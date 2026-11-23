<?php
namespace Organisation\Service;

use Application\Service\FeatureAccess;
use User\Entity\User;

/**
 * Espace membres > Organisation.
 *
 * Voir et repondre : fonctionnalite "organisation" ouverte a son niveau
 * (config features : GOD seulement pour l'instant), et etre membre de
 * l'association ou admin, comme le briefing. Compte bloque : jamais.
 * Publier : en plus, fonctionnalite "organisation_edition".
 */
class OrganisationAccess
{
    public const FEATURE = 'organisation';
    public const FEATURE_EDITION = 'organisation_edition';

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
        return ($user->getIsMember() || $user->hasAdminAccess()) && !$user->isBlocked() && !$user->isDeactivated();
    }

    public function canEdit(?User $user): bool
    {
        return $this->canAccess($user) && $this->featureAccess->isAllowed(self::FEATURE_EDITION, $user);
    }
}
