<?php
namespace Messagerie\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="message", indexes={
 *     @ORM\Index(name="idx_message_conversation", columns={"conversation_id", "created_at"}),
 *     @ORM\Index(name="idx_message_sender", columns={"sender_id", "created_at"})
 * })
 */
class Message
{
    public const BODY_MAX = 2000;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Messagerie\Entity\Conversation", inversedBy="messages")
     * @ORM\JoinColumn(name="conversation_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $conversation;

    /**
     * Auteur ; null si son compte a ete supprime.
     *
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="sender_id", referencedColumnName="iduser", nullable=true, onDelete="SET NULL")
     */
    private $sender;

    /** @ORM\Column(type="text") */
    private $body;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    public function __construct(Conversation $conversation, ?User $sender, string $body)
    {
        $this->conversation = $conversation;
        $this->sender = $sender;
        $this->body = $body;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function isFrom(?User $user): bool
    {
        return $user !== null && $this->sender !== null && $this->sender->getIdUser() === $user->getIdUser();
    }

    public function getBody(): string
    {
        return (string) $this->body;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public const REDACTED = '[Message retiré par la modération]';

    /**
     * Retrait par la moderation : le texte est remplace, la trace du message reste.
     */
    public function redact(): void
    {
        $this->body = self::REDACTED;
    }

    public function isRedacted(): bool
    {
        return $this->body === self::REDACTED;
    }
}
