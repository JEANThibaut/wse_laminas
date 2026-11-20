<?php
namespace Messagerie\Repository;

use Annonce\Entity\Annonce;
use Doctrine\ORM\EntityRepository;
use Messagerie\Entity\Conversation;
use Messagerie\Entity\ConversationParticipant;
use User\Entity\User;

class ConversationRepository extends EntityRepository
{
    /**
     * @return ConversationParticipant[] fils non archives du membre, le plus recent d'abord
     */
    public function findInbox(User $user): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p', 'c')
            ->from(ConversationParticipant::class, 'p')
            ->innerJoin('p.conversation', 'c')
            ->where('p.user = :user')
            ->andWhere('p.archivedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Identifiants des fils ou un message d'autrui est posterieur a la
     * derniere lecture du membre.
     *
     * @return int[]
     */
    public function unreadConversationIds(User $user): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT DISTINCT p.conversation_id
             FROM conversation_participant p
             INNER JOIN message m ON m.conversation_id = p.conversation_id
             WHERE p.user_id = :user
               AND (p.last_read_at IS NULL OR m.created_at > p.last_read_at)
               AND (m.sender_id IS NULL OR m.sender_id <> :user)',
            ['user' => $user->getIdUser()]
        )->fetchFirstColumn();
        return array_map('intval', $rows);
    }

    /**
     * Fil existant entre le membre et l'auteur de l'annonce, a propos de cette annonce.
     */
    public function findForAnnonce(Annonce $annonce, User $user): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p', 'WITH', 'p.user = :user')
            ->where('c.annonce = :annonce')
            ->andWhere('c.type = :member')
            ->setParameter('user', $user)
            ->setParameter('annonce', $annonce)
            ->setParameter('member', Conversation::TYPE_MEMBER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countMessagesSince(User $sender, \DateTime $since): int
    {
        return (int) $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM message WHERE sender_id = :sender AND created_at > :since',
            ['sender' => $sender->getIdUser(), 'since' => $since->format('Y-m-d H:i:s')]
        )->fetchOne();
    }

    /**
     * Envois groupes de l'administration, le plus recent d'abord, avec le
     * nombre de destinataires et combien ont lu.
     *
     * @return array<int, array{batch: string, subject: string, created_at: string, recipients: int, readers: int}>
     */
    public function adminBatches(int $limit = 30): array
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT c.batch, MIN(c.subject) AS subject, MIN(c.created_at) AS created_at,
                    COUNT(p.id) AS recipients, SUM(p.last_read_at IS NOT NULL) AS readers
             FROM conversation c
             INNER JOIN conversation_participant p ON p.conversation_id = c.id
             WHERE c.type = :admin AND c.batch IS NOT NULL
             GROUP BY c.batch
             ORDER BY MIN(c.created_at) DESC
             LIMIT ' . (int) $limit,
            ['admin' => Conversation::TYPE_ADMIN]
        )->fetchAllAssociative();
    }
}
