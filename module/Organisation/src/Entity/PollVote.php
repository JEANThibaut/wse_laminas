<?php
namespace Organisation\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="organisation_poll_vote",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="uniq_organisation_poll_vote", columns={"option_id", "user_id"})},
 *     indexes={@ORM\Index(name="idx_organisation_poll_vote_user", columns={"user_id"})}
 * )
 */
class PollVote
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Organisation\Entity\PollOption", inversedBy="votes")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $option;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    public function __construct(PollOption $option, User $user)
    {
        $this->option = $option;
        $this->user = $user;
        $this->createdAt = new \DateTime();
    }

    public function getOption(): PollOption
    {
        return $this->option;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
