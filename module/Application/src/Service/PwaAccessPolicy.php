<?php
namespace Application\Service;

use User\Entity\User;

/**
 * Qui a acces a l'application installable et aux notifications (config 'pwa').
 * En phase de test (restricted), uniquement les comptes GOD : rien n'est
 * affiche, enregistre ni envoye pour les autres.
 */
class PwaAccessPolicy
{
    private bool $restricted;

    public function __construct(bool $restricted)
    {
        $this->restricted = $restricted;
    }

    /**
     * Toujours non pour un visiteur non connecte.
     */
    public function isAllowed(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return !$this->restricted || $user->isGod();
    }

    public function isRestricted(): bool
    {
        return $this->restricted;
    }
}
