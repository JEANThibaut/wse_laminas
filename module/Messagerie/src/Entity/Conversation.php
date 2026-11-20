<?php
namespace Messagerie\Entity;

use Annonce\Entity\Annonce;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Fil de discussion. Entre membres : deux participants, a propos d'une
 * annonce. De l'administration : un seul participant (le destinataire),
 * pas de reponse possible.
 *
 * @ORM\Entity(repositoryClass="Messagerie\Repository\ConversationRepository")
 * @ORM\Table(name="conversation", indexes={
 *     @ORM\Index(name="idx_conversation_annonce", columns={"annonce_id"}),
 *     @ORM\Index(name="idx_conversation_batch", columns={"batch"})
 * })
 */
class Conversation
{
    public const TYPE_MEMBER = 'member';
    public const TYPE_ADMIN = 'admin';

    public const SUBJECT_MAX = 120;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type="string", length=10) */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="Annonce\Entity\Annonce")
     * @ORM\JoinColumn(name="annonce_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $annonce;

    /** @ORM\Column(type="string", length=120) */
    private $subject;

    /** @ORM\Column(type="string", length=32, nullable=true) */
    private $batch;

    /** @ORM\Column(name="created_at", type="datetime") */
    private $createdAt;

    /** @ORM\Column(name="last_message_at", type="datetime") */
    private $lastMessageAt;

    /**
     * @ORM\OneToMany(targetEntity="Messagerie\Entity\ConversationParticipant", mappedBy="conversation", cascade={"persist", "remove"})
     */
    private $participants;

    /**
     * @ORM\OneToMany(targetEntity="Messagerie\Entity\Message", mappedBy="conversation", cascade={"persist", "remove"})
     * @ORM\OrderBy({"createdAt" = "ASC", "id" = "ASC"})
     */
    private $messages;

    public function __construct(string $type, string $subject, ?Annonce $annonce = null, ?string $batch = null)
    {
        $this->type = $type;
        $this->subject = mb_substr($subject, 0, self::SUBJECT_MAX);
        $this->annonce = $annonce;
        $this->batch = $batch;
        $this->createdAt = new \DateTime();
        $this->lastMessageAt = new \DateTime();
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return (string) $this->type;
    }

    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    public function getAnnonce(): ?Annonce
    {
        return $this->annonce;
    }

    public function getSubject(): string
    {
        return (string) $this->subject;
    }

    public function getBatch(): ?string
    {
        return $this->batch;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getLastMessageAt(): \DateTime
    {
        return $this->lastMessageAt;
    }

    public function addParticipant(User $user): ConversationParticipant
    {
        $participant = new ConversationParticipant($this, $user);
        $this->participants->add($participant);
        return $participant;
    }

    /**
     * @return Collection<int, ConversationParticipant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function participantFor(?User $user): ?ConversationParticipant
    {
        if (!$user) {
            return null;
        }
        foreach ($this->participants as $participant) {
            if ($participant->getUser()->getIdUser() === $user->getIdUser()) {
                return $participant;
            }
        }
        return null;
    }

    /**
     * L'autre membre d'un fil entre membres.
     */
    public function otherUser(User $user): ?User
    {
        foreach ($this->participants as $participant) {
            if ($participant->getUser()->getIdUser() !== $user->getIdUser()) {
                return $participant->getUser();
            }
        }
        return null;
    }

    public function addMessage(?User $sender, string $body): Message
    {
        $message = new Message($this, $sender, $body);
        $this->messages->add($message);
        $this->lastMessageAt = $message->getCreatedAt();
        // Un nouveau message fait reapparaitre le fil chez ceux qui l'avaient archive
        foreach ($this->participants as $participant) {
            $participant->unarchive();
        }
        return $message;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getLastMessage(): ?Message
    {
        $last = $this->messages->last();
        return $last ?: null;
    }
}
