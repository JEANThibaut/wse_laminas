<?php
namespace Moderation\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Sanction d'un membre. Avertissement : simple trace. Suspension : plus de
 * publication d'annonces jusqu'a la date de fin. Exclusion : plus de
 * publication, definitivement. Blocage : compte bloque (blacklist du site).
 *
 * @ORM\Entity
 * @ORM\Table(name="user_sanction", indexes={
 *     @ORM\Index(name="idx_user_sanction_user", columns={"user_id", "created_at"})
 * })
 */
class Sanction
{
    public const TYPE_WARNING = 'warning';
    public const TYPE_SUSPENSION = 'suspension';
    public const TYPE_BAN = 'ban';
    public const TYPE_BLACKLIST = 'blacklist';

    public const TYPES = [
        self::TYPE_WARNING => 'Avertissement',
        self::TYPE_SUSPENSION => 'Interdiction temporaire de publier',
        self::TYPE_BAN => 'Interdiction définitive de publier',
        self::TYPE_BLACKLIST => 'Compte bloqué',
    ];

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

    /** @ORM\Column(type="string", length=12) */
    private $type;

    /** @ORM\Column(type="string", length=255) */
    private $reason;

    /** @ORM\Column(name="ends_at", type="datetime", nullable=true) */
    private $endsAt;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="iduser", nullable=true, onDelete="SET NULL")
     */
    private $createdBy;

    /** @ORM\Column(name="revoked_at", type="datetime", nullable=true) */
    private $revokedAt;

    public function __construct(User $user, string $type, string $reason, ?\DateTime $endsAt, ?User $createdBy)
    {
        $this->user = $user;
        $this->type = $type;
        $this->reason = mb_substr($reason, 0, 255);
        $this->endsAt = $endsAt;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): string
    {
        return (string) $this->type;
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getReason(): string
    {
        return (string) $this->reason;
    }

    public function getEndsAt(): ?\DateTime
    {
        return $this->endsAt;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    /**
     * En vigueur : non levee, et pas encore terminee pour une suspension.
     * Un avertissement n'a pas d'effet mais reste "en vigueur" dans l'historique.
     */
    public function isActive(): bool
    {
        if ($this->revokedAt !== null) {
            return false;
        }
        return $this->endsAt === null || $this->endsAt > new \DateTime();
    }

    public function revoke(): void
    {
        $this->revokedAt = new \DateTime();
    }
}
