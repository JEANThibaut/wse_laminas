<?php
namespace Application\View\Helper;

use User\Entity\User;

/**
 * Petite coche verte a cote d'un nom de joueur dont l'email est valide.
 * Rien si l'email n'est pas valide. Usage : <?= $this->mailCheck($user) ?>
 */
class MailCheck
{
    /**
     * @param User|bool|null $subject le compte, ou directement son etat de
     *                                validation (comptes lus en SQL direct)
     */
    public function __invoke($subject): string
    {
        $validated = $subject instanceof User ? $subject->isMailValidated() : (bool) $subject;
        if (!$validated) {
            return '';
        }
        return '<i class="fa-solid fa-circle-check text-success small ms-1" title="Email validé" aria-label="Email validé" role="img"></i>';
    }
}
