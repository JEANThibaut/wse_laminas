<?php
namespace Messagerie\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Participant d'un fil : sa date de derniere lecture sert au compteur de
 * non-lus ; un fil archive disparait de sa boite jusqu'au message suivant.
 *
 * @ORM\Entity
 * @ORM\Table(name="conversation_participant",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="uniq_conversation_participant", columns={"conversation_id", "user_id"})},
 *     indexes={@ORM\Index(name="idx_conversation_participant_user", columns={"user_id"})}
 * )
 */
class ConversationParticipant
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Messagerie\Entity\Conversation", inversedBy="participants")
     * @ORM\JoinColumn(name="conversation_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $conversation;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /** @ORM\Column(name="last_read_at", type="datetime", nullable=true) */
    private $lastReadAt;

    /** @ORM\Column(name="archived_at", type="datetime", nullable=true) */
    private $archivedAt;

    public function __construct(Conversation $conversation, User $user)
    {
        $this->conversation = $conversation;
        $this->user = $user;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLastReadAt(): ?\DateTime
    {
        return $this->lastReadAt;
    }

    public function markRead(): void
    {
        $this->lastReadAt = new \DateTime();
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    public function archive(): void
    {
        $this->archivedAt = new \DateTime();
    }

    public function unarchive(): void
    {
        $this->archivedAt = null;
    }
}
