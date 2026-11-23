<?php
namespace Messagerie\Util;

use User\Entity\User;

/**
 * Nom montre aux autres membres : le pseudo, sinon le prenom. Jamais le nom
 * de famille ni l'email.
 */
final class MemberName
{
    public const ADMIN_LABEL = 'Administration WSE';

    public static function of(?User $user): string
    {
        if (!$user) {
            return 'Compte supprimé';
        }
        $nickname = trim((string) $user->getNickname());
        return $nickname !== '' ? $nickname : trim((string) $user->getFirstname());
    }
}
