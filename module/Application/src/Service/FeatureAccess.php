<?php
namespace Application\Service;

use User\Entity\User;

/**
 * Qui a acces a quelle fonctionnalite (config 'features') : pour chaque
 * fonctionnalite, true ou false par niveau. Chaque compte a un seul niveau,
 * le plus haut : god, sinon super_admin, sinon admin, sinon user.
 * Fonctionnalite ou niveau absent de la config : non. Visiteur non connecte : non.
 */
class FeatureAccess
{
    public const PWA = 'pwa';
    public const NOTIFICATION = 'notification';
    public const QUEUE = 'queue';

    /** @var array<string, array<string, bool>> */
    private array $features;

    public function __construct(array $features)
    {
        $this->features = $features;
    }

    public function isAllowed(string $feature, ?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return (bool) ($this->features[$feature][self::levelOf($user)] ?? false);
    }

    public static function levelOf(User $user): string
    {
        if ($user->isGod()) {
            return 'god';
        }
        if ($user->isSuperAdmin()) {
            return 'super_admin';
        }
        return $user->hasAdminAccess() ? 'admin' : 'user';
    }
}
