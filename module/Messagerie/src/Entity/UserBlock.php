<?php
namespace Messagerie\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * $user a bloque $blockedUser : ce dernier ne peut plus lui ecrire.
 *
 * @ORM\Entity
 * @ORM\Table(name="user_block",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="uniq_user_block", columns={"user_id", "blocked_user_id"})},
 *     indexes={@ORM\Index(name="idx_user_block_blocked", columns={"blocked_user_id"})}
 * )
 */
class UserBlock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="blocked_user_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $blockedUser;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    public function __construct(User $user, User $blockedUser)
    {
        $this->user = $user;
        $this->blockedUser = $blockedUser;
        $this->createdAt = new \DateTime();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBlockedUser(): User
    {
        return $this->blockedUser;
    }
}
