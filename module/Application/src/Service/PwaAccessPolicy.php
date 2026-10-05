<?php
namespace Application\Service;

use User\Entity\User;

/**
 * Qui a acces a l'application installable et aux notifications (config 'pwa').
 * En phase de test, seuls les comptes de allowed_emails : rien n'est affiche,
 * enregistre ni envoye pour les autres.
 */
class PwaAccessPolicy
{
    private bool $restricted;
    /** @var string[] emails en minuscules */
    private array $allowedEmails;

    public function __construct(bool $restricted, array $allowedEmails)
    {
        $this->restricted = $restricted;
        $this->allowedEmails = array_map(fn ($email) => mb_strtolower(trim($email)), $allowedEmails);
    }

    /**
     * Toujours non pour un visiteur non connecte.
     */
    public function isAllowed(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if (!$this->restricted) {
            return true;
        }
        return in_array(mb_strtolower(trim((string) $user->getEmail())), $this->allowedEmails, true);
    }

    public function isRestricted(): bool
    {
        return $this->restricted;
    }

    /** @return string[] */
    public function getAllowedEmails(): array
    {
        return $this->allowedEmails;
    }
}
