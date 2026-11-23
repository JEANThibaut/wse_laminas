<?php
namespace Organisation\View\Helper;

use Organisation\Service\OrganisationAccess as OrganisationAccessService;
use User\Entity\User;

/**
 * Entree "Organisation" du menu : memes regles que les pages (fonctionnalite
 * ouverte ET membre de l'association ou admin).
 * Usage : <?php if ($this->organisationAccess($this->currentUser)): ?>
 */
class OrganisationAccess
{
    private OrganisationAccessService $access;

    public function __construct(OrganisationAccessService $access)
    {
        $this->access = $access;
    }

    public function __invoke(?User $user): bool
    {
        return $this->access->canAccess($user);
    }
}
