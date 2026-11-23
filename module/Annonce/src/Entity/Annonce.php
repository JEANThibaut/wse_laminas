<?php
namespace Annonce\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Petite annonce d'un membre.
 *
 * Une annonce "en ligne" dont la date d'expiration est passee est expiree :
 * pas de statut a part, la date suffit (aucune tache planifiee necessaire).
 *
 * @ORM\Entity(repositoryClass="Annonce\Repository\AnnonceRepository")
 * @ORM\Table(name="annonce", indexes={
 *     @ORM\Index(name="idx_annonce_status_expires", columns={"status", "expires_at"}),
 *     @ORM\Index(name="idx_annonce_user", columns={"user_id"})
 * })
 */
class Annonce
{
    public const STATUS_ONLINE = 'online';
    // Retiree par son auteur
    public const STATUS_WITHDRAWN = 'withdrawn';
    // Retiree par la moderation
    public const STATUS_REMOVED = 'removed';
    // Masquee automatiquement (trop de signalements), en attente de moderation
    public const STATUS_HIDDEN = 'hidden';
    // En attente de validation (reglage "validation avant publication")
    public const STATUS_PENDING = 'pending';

    public const TITLE_MAX = 80;
    public const DESCRIPTION_MAX = 1500;
    public const PRICE_MAX = 100000;
    public const PHOTOS_MAX = 4;
    public const TAGS_MAX = 3;

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

    /** @ORM\Column(type="string", length=80) */
    private $title;

    /** @ORM\Column(type="text") */
    private $description;

    /**
     * En euros ; null = a donner.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $price;

    /** @ORM\Column(type="string", length=20) */
    private $status = self::STATUS_ONLINE;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    /** @ORM\Column(name="updated_at", type="datetime") */
    private $updatedAt;

    /** @ORM\Column(name="expires_at", type="datetime") */
    private $expiresAt;

    /** @ORM\Column(name="renew_count", type="integer", options={"default": 0}) */
    private $renewCount = 0;

    /**
     * Retrait (auteur ou moderation) : point de depart de la purge.
     *
     * @ORM\Column(name="closed_at", type="datetime", nullable=true)
     */
    private $closedAt;

    /**
     * Derniere decision de moderation (validation, refus, retrait ou
     * classement d'un signalement) : qui l'a prise et quand. Le statut dit
     * de quelle decision il s'agit.
     *
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="moderated_by", referencedColumnName="iduser", nullable=true, onDelete="SET NULL")
     */
    private $moderatedBy;

    /** @ORM\Column(name="moderated_at", type="datetime", nullable=true) */
    private $moderatedAt;

    /**
     * @ORM\OneToMany(targetEntity="Annonce\Entity\AnnoncePhoto", mappedBy="annonce", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    private $photos;

    /**
     * @ORM\ManyToMany(targetEntity="Annonce\Entity\AnnonceTag")
     * @ORM\JoinTable(name="annonce_tag_link",
     *     joinColumns={@ORM\JoinColumn(name="annonce_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $tags;

    public function __construct(User $user, \DateTime $expiresAt)
    {
        $this->user = $user;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->expiresAt = $expiresAt;
        $this->photos = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $user->getIdUser() === $this->user->getIdUser();
    }

    public function getTitle(): string
    {
        return (string) $this->title;
    }

    public function getDescription(): string
    {
        return (string) $this->description;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function isFree(): bool
    {
        return $this->price === null;
    }

    /**
     * Titre, description, prix (null = a donner) et tags (1 a 3).
     *
     * @param AnnonceTag[] $tags
     */
    public function update(string $title, string $description, ?int $price, array $tags): self
    {
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
        $this->tags->clear();
        foreach ($tags as $tag) {
            $this->tags->add($tag);
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_ONLINE && $this->expiresAt <= new \DateTime();
    }

    /**
     * Fermee pour son auteur (ni modification, ni renouvellement, ni retrait) :
     * retiree, ou masquee en attendant la moderation. Une annonce en attente
     * de validation reste modifiable et retirable.
     */
    public function isClosed(): bool
    {
        return $this->status !== self::STATUS_ONLINE && $this->status !== self::STATUS_PENDING;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mise en attente de validation (creation ou modification par l'auteur).
     */
    public function submitForValidation(): self
    {
        if ($this->status === self::STATUS_ONLINE || $this->status === self::STATUS_PENDING) {
            $this->status = self::STATUS_PENDING;
        }
        return $this;
    }

    /**
     * Validee par la moderation : en ligne, duree de vie complete a partir d'aujourd'hui.
     */
    public function approve(\DateTime $expiresAt, User $moderator): self
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->status = self::STATUS_ONLINE;
            $this->expiresAt = $expiresAt;
            $this->updatedAt = new \DateTime();
            $this->markModerated($moderator);
        }
        return $this;
    }

    /**
     * Trace de la decision de moderation : qui, quand.
     */
    public function markModerated(User $moderator): self
    {
        $this->moderatedBy = $moderator;
        $this->moderatedAt = new \DateTime();
        return $this;
    }

    public function getModeratedBy(): ?User
    {
        return $this->moderatedBy;
    }

    public function getModeratedAt(): ?\DateTime
    {
        return $this->moderatedAt;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function getRenewCount(): int
    {
        return (int) $this->renewCount;
    }

    public function renew(\DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        $this->renewCount = $this->getRenewCount() + 1;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function close(string $status): self
    {
        $this->status = $status;
        $this->closedAt = new \DateTime();
        return $this;
    }

    public function isHidden(): bool
    {
        return $this->status === self::STATUS_HIDDEN;
    }

    /**
     * Masquage en attente de moderation : pas de date de retrait, la purge
     * ne la touche pas tant que la moderation n'a pas tranche.
     */
    public function hide(): self
    {
        if ($this->status === self::STATUS_ONLINE) {
            $this->status = self::STATUS_HIDDEN;
        }
        return $this;
    }

    /**
     * Signalements classes sans suite : l'annonce masquee revient en ligne.
     */
    public function restore(): self
    {
        if ($this->status === self::STATUS_HIDDEN) {
            $this->status = self::STATUS_ONLINE;
        }
        return $this;
    }

    public function getClosedAt(): ?\DateTime
    {
        return $this->closedAt;
    }

    /**
     * @return Collection<int, AnnoncePhoto>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function getCoverPhoto(): ?AnnoncePhoto
    {
        $first = $this->photos->first();
        return $first ?: null;
    }

    public function addPhoto(string $filename): AnnoncePhoto
    {
        $last = $this->photos->last();
        $photo = new AnnoncePhoto($this, $filename, $last ? $last->getPosition() + 1 : 1);
        $this->photos->add($photo);
        return $photo;
    }

    public function removePhoto(AnnoncePhoto $photo): void
    {
        $this->photos->removeElement($photo);
    }

    /**
     * @return Collection<int, AnnonceTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function hasTag(AnnonceTag $tag): bool
    {
        return $this->tags->contains($tag);
    }
}
