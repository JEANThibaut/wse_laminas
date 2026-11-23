<?php
namespace Moderation\Entity;

use Annonce\Entity\Annonce;
use Doctrine\ORM\Mapping as ORM;
use Messagerie\Entity\Message;
use User\Entity\User;

/**
 * Signalement d'une annonce ou d'un message par un membre.
 *
 * @ORM\Entity
 * @ORM\Table(name="report",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_report_annonce", columns={"reporter_id", "annonce_id"}),
 *         @ORM\UniqueConstraint(name="uniq_report_message", columns={"reporter_id", "message_id"})
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_report_status", columns={"status"}),
 *         @ORM\Index(name="idx_report_annonce", columns={"annonce_id"}),
 *         @ORM\Index(name="idx_report_message", columns={"message_id"}),
 *         @ORM\Index(name="idx_report_reporter", columns={"reporter_id", "created_at"})
 *     }
 * )
 */
class Report
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_ACTIONED = 'actioned';

    /** Motifs : cle => libelle, aussi utilises comme motif de retrait */
    public const REASONS = [
        'off_topic' => 'Hors sujet (pas de l’airsoft)',
        'forbidden' => 'Objet interdit ou illégal',
        'scam' => 'Arnaque ou fraude',
        'inappropriate' => 'Contenu injurieux ou inapproprié',
        'other' => 'Autre',
    ];

    public const COMMENT_MAX = 500;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="reporter_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $reporter;

    /**
     * @ORM\ManyToOne(targetEntity="Annonce\Entity\Annonce")
     * @ORM\JoinColumn(name="annonce_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $annonce;

    /**
     * @ORM\ManyToOne(targetEntity="Messagerie\Entity\Message")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $message;

    /** @ORM\Column(type="string", length=20) */
    private $reason;

    /** @ORM\Column(type="string", length=500, nullable=true) */
    private $comment;

    /** @ORM\Column(type="string", length=10) */
    private $status = self::STATUS_PENDING;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="handled_by", referencedColumnName="iduser", nullable=true, onDelete="SET NULL")
     */
    private $handledBy;

    /** @ORM\Column(name="handled_at", type="datetime", nullable=true) */
    private $handledAt;

    private function __construct(User $reporter, string $reason, ?string $comment)
    {
        $this->reporter = $reporter;
        $this->reason = isset(self::REASONS[$reason]) ? $reason : 'other';
        $this->comment = $comment !== null && $comment !== '' ? mb_substr($comment, 0, self::COMMENT_MAX) : null;
        $this->createdAt = new \DateTime();
    }

    public static function forAnnonce(User $reporter, Annonce $annonce, string $reason, ?string $comment): self
    {
        $report = new self($reporter, $reason, $comment);
        $report->annonce = $annonce;
        return $report;
    }

    public static function forMessage(User $reporter, Message $message, string $reason, ?string $comment): self
    {
        $report = new self($reporter, $reason, $comment);
        $report->message = $message;
        return $report;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporter(): User
    {
        return $this->reporter;
    }

    public function getAnnonce(): ?Annonce
    {
        return $this->annonce;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function getReason(): string
    {
        return (string) $this->reason;
    }

    public function getReasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? self::REASONS['other'];
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function handle(string $status, User $admin): void
    {
        $this->status = $status;
        $this->handledBy = $admin;
        $this->handledAt = new \DateTime();
    }
}
