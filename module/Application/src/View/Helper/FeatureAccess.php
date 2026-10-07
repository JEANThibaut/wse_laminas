<?php
namespace Application\View\Helper;

use Application\Service\FeatureAccess as FeatureAccessService;
use User\Entity\User;

/**
 * Le compte a-t-il acces a la fonctionnalite (config 'features') ?
 * Usage : <?php if ($this->featureAccess('pwa', $this->currentUser)): ?>
 */
class FeatureAccess
{
    private FeatureAccessService $features;

    public function __construct(FeatureAccessService $features)
    {
        $this->features = $features;
    }

    public function __invoke(string $feature, ?User $user): bool
    {
        return $this->features->isAllowed($feature, $user);
    }
}
