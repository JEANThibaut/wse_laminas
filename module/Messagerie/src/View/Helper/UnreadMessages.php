<?php
namespace Messagerie\View\Helper;

use Messagerie\Service\MessagerieAccess;
use Messagerie\Service\MessagerieManager;
use User\Entity\User;

/**
 * Compteur du menu : nombre de conversations avec des messages non lus,
 * null si le compte n'a pas acces a la messagerie (entree du menu masquee).
 * Usage : <?php $unread = $this->unreadMessages($this->currentUser); ?>
 */
class UnreadMessages
{
    private MessagerieAccess $access;
    private MessagerieManager $manager;

    public function __construct(MessagerieAccess $access, MessagerieManager $manager)
    {
        $this->access = $access;
        $this->manager = $manager;
    }

    public function __invoke(?User $user): ?int
    {
        if (!$this->access->canAccess($user)) {
            return null;
        }
        try {
            return $this->manager->unreadCount($user);
        } catch (\Throwable $e) {
            // Tables absentes (migration pas encore jouee) : le menu ne doit jamais casser
            return 0;
        }
    }
}
