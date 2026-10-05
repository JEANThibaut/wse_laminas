<?php
namespace Application\View\Helper;

use Application\Service\PwaAccessPolicy;
use User\Entity\User;

/**
 * Le compte a-t-il acces a l'application installable et aux notifications ?
 * Usage : <?php if ($this->pwaAccess($this->currentUser)): ?>
 */
class PwaAccess
{
    private PwaAccessPolicy $policy;

    public function __construct(PwaAccessPolicy $policy)
    {
        $this->policy = $policy;
    }

    public function __invoke(?User $user): bool
    {
        return $this->policy->isAllowed($user);
    }
}
