<?php
namespace Game\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Place d'un joueur dans la file d'attente d'une partie complete.
 * L'ordre d'arrivee (createdAt, puis id) fait foi : quand une place se libere,
 * elle est proposee au premier en attente, qui a un delai pour l'accepter.
 *
 * @ORM\Entity(repositoryClass="Game\Repository\QueueRepository")
 * @ORM\Table(name="game_queue", indexes={
 *     @ORM\Index(name="idx_game_queue_game_status", columns={"game_id", "status"}),
 *     @ORM\Index(name="idx_game_queue_user", columns={"user_id"}),
 *     @ORM\Index(name="idx_game_queue_expires", columns={"status", "offer_expires_at"})
 * })
 */
class QueueEntry
{
    // En file, dans l'ordre d'arrivee
    public const STATUS_WAITING = 'waiting';
    // Une place lui est proposee, jusqu'a offerExpiresAt
    public const STATUS_OFFERED = 'offered';
    // A accepte la place : il est inscrit a la partie
    public const STATUS_ACCEPTED = 'accepted';
    // A refuse la place proposee
    public const STATUS_DECLINED = 'declined';
    // N'a pas repondu a temps : la place est passee au suivant
    public const STATUS_EXPIRED = 'expired';
    // A quitte la file de lui-meme
    public const STATUS_LEFT = 'left';
    // Retire de la file par un admin
    public const STATUS_REMOVED = 'removed';

    // Entrees encore dans la file
    public const OPEN_STATUSES = [self::STATUS_WAITING, self::STATUS_OFFERED];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Game\Entity\Game")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="idgame", nullable=false, onDelete="CASCADE")
     */
    private $game;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * Un des self::STATUS_*.
     *
     * @ORM\Column(type="string", length=16)
     */
    private $status = self::STATUS_WAITING;

    /**
     * Arrivee dans la file : fixe l'ordre.
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(name="offered_at", type="datetime", nullable=true)
     */
    private $offeredAt;

    /**
     * @ORM\Column(name="offer_expires_at", type="datetime", nullable=true)
     */
    private $offerExpiresAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    public function __construct(Game $game, User $user)
    {
        $this->game = $game;
        $this->user = $user;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function isOffered(): bool
    {
        return $this->status === self::STATUS_OFFERED;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getOfferedAt(): ?\DateTimeInterface
    {
        return $this->offeredAt;
    }

    public function getOfferExpiresAt(): ?\DateTimeInterface
    {
        return $this->offerExpiresAt;
    }

    public function offer(\DateTimeInterface $expiresAt): void
    {
        $this->status = self::STATUS_OFFERED;
        $this->offeredAt = new \DateTime();
        $this->offerExpiresAt = \DateTime::createFromInterface($expiresAt);
        $this->touch();
    }

    /**
     * Ferme l'entree (acceptee, refusee, expiree, quittee ou retiree).
     */
    public function close(string $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
