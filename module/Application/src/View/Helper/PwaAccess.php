<?php
namespace Application\View\Helper;

use User\Entity\User;

/**
 * Le compte peut-il installer le site comme application (config 'pwa') ?
 * Toujours non pour un visiteur non connecte.
 */
class PwaAccess
{
    private bool $restricted;
    /** @var string[] emails en minuscules */
    private array $allowedEmails;

    public function __construct(bool $restricted, array $allowedEmails)
    {
        $this->restricted = $restricted;
        $this->allowedEmails = array_map(fn ($email) => mb_strtolower(trim($email)), $allowedEmails);
    }

    public function __invoke(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if (!$this->restricted) {
            return true;
        }
        return in_array(mb_strtolower(trim((string) $user->getEmail())), $this->allowedEmails, true);
    }
}
