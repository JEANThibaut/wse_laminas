<?php
namespace Organisation\View\Helper;

use Organisation\Service\OrganisationAccess;
use Organisation\Service\OrganisationManager;
use User\Entity\User;

/**
 * Publications epinglees de l'espace Organisation, affichees en tete de
 * l'accueil. Vide pour tout compte qui n'a pas acces a l'espace : l'accueil
 * ne change pas pour eux.
 * Usage : <?php foreach ($this->organisationPinned($this->currentUser) as $post): ?>
 */
class OrganisationPinned
{
    private OrganisationAccess $access;
    private OrganisationManager $manager;

    public function __construct(OrganisationAccess $access, OrganisationManager $manager)
    {
        $this->access = $access;
        $this->manager = $manager;
    }

    public function __invoke(?User $user): array
    {
        if (!$this->access->canAccess($user)) {
            return [];
        }
        try {
            return $this->manager->posts()->pinned();
        } catch (\Throwable $e) {
            // Table absente (migration pas encore jouee) : l'accueil ne doit jamais casser
            return [];
        }
    }
}
