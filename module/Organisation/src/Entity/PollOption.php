<?php
namespace Organisation\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="organisation_poll_option", indexes={
 *     @ORM\Index(name="idx_organisation_poll_option_post", columns={"post_id"})
 * })
 */
class PollOption
{
    public const LABEL_MAX = 120;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Organisation\Entity\Post", inversedBy="options")
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $post;

    /** @ORM\Column(type="string", length=120) */
    private $label;

    /** @ORM\Column(type="integer") */
    private $position;

    /**
     * @ORM\OneToMany(targetEntity="Organisation\Entity\PollVote", mappedBy="option", cascade={"remove"})
     */
    private $votes;

    public function __construct(Post $post, string $label, int $position)
    {
        $this->post = $post;
        $this->label = mb_substr($label, 0, self::LABEL_MAX);
        $this->position = $position;
        $this->votes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getLabel(): string
    {
        return (string) $this->label;
    }

    /**
     * @return Collection<int, PollVote>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }
}
