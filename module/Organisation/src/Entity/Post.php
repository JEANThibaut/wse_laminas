<?php
namespace Organisation\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Publication de l'espace Organisation : message, sondage ou evenement.
 *
 * @ORM\Entity(repositoryClass="Organisation\Repository\PostRepository")
 * @ORM\Table(name="organisation_post", indexes={
 *     @ORM\Index(name="idx_organisation_post_created", columns={"pinned", "created_at"}),
 *     @ORM\Index(name="idx_organisation_post_starts", columns={"type", "starts_at"})
 * })
 */
class Post
{
    public const TYPE_MESSAGE = 'message';
    public const TYPE_POLL = 'poll';
    public const TYPE_EVENT = 'event';

    public const TYPES = [
        self::TYPE_MESSAGE => 'Message',
        self::TYPE_POLL => 'Sondage',
        self::TYPE_EVENT => 'Événement',
    ];

    public const TITLE_MAX = 120;
    public const BODY_MAX = 5000;
    public const LOCATION_MAX = 120;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type="string", length=10) */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="iduser", nullable=true, onDelete="SET NULL")
     */
    private $author;

    /** @ORM\Column(type="string", length=120) */
    private $title;

    /** @ORM\Column(type="text", nullable=true) */
    private $body;

    /** @ORM\Column(type="boolean", options={"default": 0}) */
    private $pinned = false;

    /** @ORM\Column(name="starts_at", type="datetime", nullable=true) */
    private $startsAt;

    /** @ORM\Column(name="ends_at", type="datetime", nullable=true) */
    private $endsAt;

    /** @ORM\Column(type="string", length=120, nullable=true) */
    private $location;

    /** @ORM\Column(type="boolean", options={"default": 0}) */
    private $multiple = false;

    /** @ORM\Column(name="closes_at", type="datetime", nullable=true) */
    private $closesAt;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    /** @ORM\Column(name="updated_at", type="datetime") */
    private $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="Organisation\Entity\PollOption", mappedBy="post", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    private $options;

    /**
     * @ORM\OneToMany(targetEntity="Organisation\Entity\Rsvp", mappedBy="post", cascade={"remove"})
     */
    private $rsvps;

    public function __construct(string $type, ?User $author)
    {
        $this->type = isset(self::TYPES[$type]) ? $type : self::TYPE_MESSAGE;
        $this->author = $author;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->options = new ArrayCollection();
        $this->rsvps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return (string) $this->type;
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function isPoll(): bool
    {
        return $this->type === self::TYPE_POLL;
    }

    public function isEvent(): bool
    {
        return $this->type === self::TYPE_EVENT;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function getTitle(): string
    {
        return (string) $this->title;
    }

    public function getBody(): string
    {
        return (string) $this->body;
    }

    public function isPinned(): bool
    {
        return (bool) $this->pinned;
    }

    public function setPinned(bool $pinned): self
    {
        $this->pinned = $pinned;
        return $this;
    }

    public function getStartsAt(): ?\DateTime
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?\DateTime
    {
        return $this->endsAt;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->multiple;
    }

    public function getClosesAt(): ?\DateTime
    {
        return $this->closesAt;
    }

    /**
     * Plus de vote ni de reponse : date de cloture passee. Un evenement sans
     * date de cloture se ferme a son debut.
     */
    public function isClosed(): bool
    {
        $limit = $this->closesAt ?? ($this->isEvent() ? $this->startsAt : null);
        return $limit !== null && $limit <= new \DateTime();
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function update(string $title, string $body, ?\DateTime $closesAt): self
    {
        $this->title = $title;
        $this->body = $body !== '' ? $body : null;
        $this->closesAt = $closesAt;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setEvent(\DateTime $startsAt, ?\DateTime $endsAt, ?string $location): self
    {
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->location = $location !== null && $location !== '' ? $location : null;
        return $this;
    }

    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * @return Collection<int, PollOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(string $label): PollOption
    {
        $option = new PollOption($this, $label, $this->options->count() + 1);
        $this->options->add($option);
        return $option;
    }

    /**
     * @return Collection<int, Rsvp>
     */
    public function getRsvps(): Collection
    {
        return $this->rsvps;
    }
}
