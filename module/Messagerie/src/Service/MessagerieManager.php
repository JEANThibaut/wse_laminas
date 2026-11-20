<?php
namespace Messagerie\Service;

use Annonce\Entity\Annonce;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Messagerie\Entity\Conversation;
use Messagerie\Entity\ConversationParticipant;
use Messagerie\Entity\Message;
use Messagerie\Entity\UserBlock;
use Messagerie\Repository\ConversationRepository;
use User\Entity\User;

/**
 * Envoi des messages (entre membres et de l'administration), lecture,
 * archivage et blocage.
 */
class MessagerieManager
{
    // Anti-abus : messages par membre sur la periode (le GOD n'est pas limite)
    private const RATE_LIMIT = 30;
    private const RATE_PERIOD = '-10 minutes';

    public const AUDIENCE_ONE = 'one';
    public const AUDIENCE_MEMBERS = 'members';
    public const AUDIENCE_ALL = 'all';

    private EntityManager $entityManager;
    private ?int $unreadCache = null;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function conversations(): ConversationRepository
    {
        return $this->entityManager->getRepository(Conversation::class);
    }

    /**
     * Nombre de fils avec des messages non lus (compteur du menu), calcule une fois par requete.
     */
    public function unreadCount(User $user): int
    {
        if ($this->unreadCache === null) {
            $this->unreadCache = count($this->conversations()->unreadConversationIds($user));
        }
        return $this->unreadCache;
    }

    /**
     * Ecrit a l'auteur d'une annonce : dans le fil existant pour cette annonce, ou un nouveau.
     *
     * @return array{conversation: ?Conversation, error: ?string}
     */
    public function contactAboutAnnonce(User $sender, Annonce $annonce, $rawBody): array
    {
        $seller = $annonce->getUser();
        if (!$annonce->isOnline()) {
            return ['conversation' => null, 'error' => "Cette annonce n'est plus en ligne."];
        }
        if ($annonce->isOwnedBy($sender)) {
            return ['conversation' => null, 'error' => 'Vous ne pouvez pas vous écrire à vous-même.'];
        }
        $existing = $this->conversations()->findForAnnonce($annonce, $sender);
        if ($existing) {
            $error = $this->reply($existing, $sender, $rawBody);
            return ['conversation' => $error ? null : $existing, 'error' => $error];
        }
        if ($error = $this->checkCanSend($sender, $seller)) {
            return ['conversation' => null, 'error' => $error];
        }
        [$body, $error] = $this->cleanBody($rawBody);
        if ($error) {
            return ['conversation' => null, 'error' => $error];
        }

        $conversation = new Conversation(Conversation::TYPE_MEMBER, $annonce->getTitle(), $annonce);
        $conversation->addParticipant($sender)->markRead();
        $conversation->addParticipant($seller);
        $conversation->addMessage($sender, $body);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();
        return ['conversation' => $conversation, 'error' => null];
    }

    /**
     * Reponse dans un fil entre membres.
     *
     * @return string|null erreur affichable, null si envoye
     */
    public function reply(Conversation $conversation, User $sender, $rawBody): ?string
    {
        $participant = $conversation->participantFor($sender);
        if (!$participant || $conversation->isAdmin()) {
            return 'Vous ne pouvez pas répondre dans cette conversation.';
        }
        $other = $conversation->otherUser($sender);
        if (!$other) {
            return 'Votre correspondant a quitté le site.';
        }
        if ($error = $this->checkCanSend($sender, $other)) {
            return $error;
        }
        [$body, $error] = $this->cleanBody($rawBody);
        if ($error) {
            return $error;
        }
        $conversation->addMessage($sender, $body);
        $participant->markRead();
        $this->entityManager->flush();
        return null;
    }

    public function markRead(ConversationParticipant $participant): void
    {
        $participant->markRead();
        $this->entityManager->flush();
        $this->unreadCache = null;
    }

    public function archive(ConversationParticipant $participant): void
    {
        $participant->markRead();
        $participant->archive();
        $this->entityManager->flush();
    }

    public function hasBlocked(User $user, User $target): bool
    {
        return $this->findBlock($user, $target) !== null;
    }

    /**
     * Bloque ou debloque : renvoie le nouvel etat (true = bloque).
     */
    public function toggleBlock(User $user, User $target): bool
    {
        $block = $this->findBlock($user, $target);
        if ($block) {
            $this->entityManager->remove($block);
            $this->entityManager->flush();
            return false;
        }
        $this->entityManager->persist(new UserBlock($user, $target));
        $this->entityManager->flush();
        return true;
    }

    /**
     * Destinataires d'un envoi de l'administration.
     *
     * @return User[]
     */
    public function audience(string $audience, int $userId = 0): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');
        if ($audience === self::AUDIENCE_ONE) {
            $user = $userId > 0 ? $this->entityManager->getRepository(User::class)->find($userId) : null;
            return $user ? [$user] : [];
        }
        // Comptes valides, ni bloques ni desactives (NULL = actif)
        $qb->where('u.mailValidation = 1')
            ->andWhere('u.blacklist = 0 OR u.blacklist IS NULL')
            ->andWhere('u.isActive = 1 OR u.isActive IS NULL');
        if ($audience === self::AUDIENCE_MEMBERS) {
            $qb->andWhere('u.member = 1');
        } elseif ($audience !== self::AUDIENCE_ALL) {
            return [];
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Message de l'administration : un fil par destinataire, sans reponse possible.
     *
     * @param User[] $recipients
     * @return array{count: int, error: ?string}
     */
    public function broadcast(User $admin, array $recipients, string $subject, $rawBody): array
    {
        $subject = mb_substr(InputSanitizer::cleanString($subject), 0, Conversation::SUBJECT_MAX);
        if ($subject === '' || !mb_check_encoding($subject, 'UTF-8')) {
            return ['count' => 0, 'error' => "L'objet est obligatoire."];
        }
        [$body, $error] = $this->cleanBody($rawBody);
        if ($error) {
            return ['count' => 0, 'error' => $error];
        }
        if (!$recipients) {
            return ['count' => 0, 'error' => 'Aucun destinataire.'];
        }

        $batch = bin2hex(random_bytes(16));
        foreach ($recipients as $index => $recipient) {
            $conversation = new Conversation(Conversation::TYPE_ADMIN, $subject, null, $batch);
            $conversation->addParticipant($recipient);
            $conversation->addMessage($admin, $body);
            $this->entityManager->persist($conversation);
            // Par paquets : la memoire reste stable meme pour tous les comptes
            if (($index + 1) % 100 === 0) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        return ['count' => count($recipients), 'error' => null];
    }

    /**
     * Blocage et anti-abus avant l'envoi a $recipient.
     */
    private function checkCanSend(User $sender, User $recipient): ?string
    {
        if ($this->hasBlocked($recipient, $sender)) {
            return 'Ce membre ne reçoit plus vos messages.';
        }
        if ($this->hasBlocked($sender, $recipient)) {
            return 'Vous avez bloqué ce membre : débloquez-le pour lui écrire.';
        }
        if (!$sender->isGod() && $this->conversations()->countMessagesSince($sender, new \DateTime(self::RATE_PERIOD)) >= self::RATE_LIMIT) {
            return "Vous envoyez beaucoup de messages : patientez quelques minutes.";
        }
        return null;
    }

    /**
     * @return array{0: string, 1: ?string} texte nettoye, erreur eventuelle
     */
    private function cleanBody($rawBody): array
    {
        $body = InputSanitizer::cleanText($rawBody);
        if (!mb_check_encoding($body, 'UTF-8')) {
            return ['', 'Le message contient des caractères non reconnus.'];
        }
        if ($body === '') {
            return ['', 'Le message est vide.'];
        }
        if (mb_strlen($body) > Message::BODY_MAX) {
            return ['', 'Message trop long (' . Message::BODY_MAX . ' caractères maximum).'];
        }
        return [$body, null];
    }

    private function findBlock(User $user, User $target): ?UserBlock
    {
        return $this->entityManager->getRepository(UserBlock::class)->findOneBy(['user' => $user, 'blockedUser' => $target]);
    }
}
