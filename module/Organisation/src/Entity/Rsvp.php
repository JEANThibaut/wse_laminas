<?php
namespace Organisation\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Reponse d'un membre a un evenement : je participe / je ne participe pas.
 *
 * @ORM\Entity
 * @ORM\Table(name="organisation_rsvp",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="uniq_organisation_rsvp", columns={"post_id", "user_id"})},
 *     indexes={@ORM\Index(name="idx_organisation_rsvp_user", columns={"user_id"})}
 * )
 */
class Rsvp
{
    public const YES = 'yes';
    public const NO = 'no';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Organisation\Entity\Post", inversedBy="rsvps")
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $post;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /** @ORM\Column(type="string", length=3) */
    private $answer;

    /** @ORM\Column(name="updated_at", type="datetime") */
    private $updatedAt;

    public function __construct(Post $post, User $user, string $answer)
    {
        $this->post = $post;
        $this->user = $user;
        $this->setAnswer($answer);
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAnswer(): string
    {
        return (string) $this->answer;
    }

    public function isYes(): bool
    {
        return $this->answer === self::YES;
    }

    public function setAnswer(string $answer): void
    {
        $this->answer = $answer === self::YES ? self::YES : self::NO;
        $this->updatedAt = new \DateTime();
    }
}
