<?php
namespace Game\Repository;

use Doctrine\ORM\EntityRepository;
use Game\Entity\Game;
use Game\Entity\QueueEntry;
use User\Entity\User;

class QueueRepository extends EntityRepository
{
    /**
     * File d'une partie (en attente et place proposee), dans l'ordre d'arrivee.
     *
     * @return QueueEntry[]
     */
    public function findOpenForGame(Game $game): array
    {
        return $this->createQueryBuilder('q')
            ->join('q.user', 'u')
            ->addSelect('u')
            ->where('q.game = :game')
            ->andWhere('q.status IN (:statuses)')
            ->setParameter('game', $game)
            ->setParameter('statuses', QueueEntry::OPEN_STATUSES)
            ->orderBy('q.createdAt', 'ASC')
            ->addOrderBy('q.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Premier joueur en attente (sans place proposee), ou null.
     */
    public function findFirstWaiting(Game $game): ?QueueEntry
    {
        return $this->createQueryBuilder('q')
            ->where('q.game = :game')
            ->andWhere('q.status = :waiting')
            ->setParameter('game', $game)
            ->setParameter('waiting', QueueEntry::STATUS_WAITING)
            ->orderBy('q.createdAt', 'ASC')
            ->addOrderBy('q.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Entree encore ouverte d'un joueur pour une partie.
     */
    public function findOpenEntry(Game $game, User $user): ?QueueEntry
    {
        return $this->createQueryBuilder('q')
            ->where('q.game = :game')
            ->andWhere('q.user = :user')
            ->andWhere('q.status IN (:statuses)')
            ->setParameter('game', $game)
            ->setParameter('user', $user)
            ->setParameter('statuses', QueueEntry::OPEN_STATUSES)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Places reservees : proposees et pas encore acceptees.
     * Sert au calcul des places libres sur l'accueil et a l'inscription : si
     * la table n'existe pas encore (migration non jouee), 0 plutot que de casser
     * le site.
     */
    public function countOffered(Game $game): int
    {
        try {
            return (int) $this->createQueryBuilder('q')
                ->select('COUNT(q.id)')
                ->where('q.game = :game')
                ->andWhere('q.status = :offered')
                ->setParameter('game', $game)
                ->setParameter('offered', QueueEntry::STATUS_OFFERED)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $e) {
            error_log('game_queue absente : ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Position d'une entree en attente parmi les joueurs en attente (1 = premier).
     */
    public function getWaitingPosition(QueueEntry $entry): int
    {
        return 1 + (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.game = :game')
            ->andWhere('q.status = :waiting')
            ->andWhere('q.createdAt < :createdAt OR (q.createdAt = :createdAt AND q.id < :id)')
            ->setParameter('game', $entry->getGame())
            ->setParameter('waiting', QueueEntry::STATUS_WAITING)
            ->setParameter('createdAt', $entry->getCreatedAt())
            ->setParameter('id', $entry->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Places proposees dont le delai est depasse, toutes parties confondues.
     *
     * @return QueueEntry[]
     */
    public function findExpiredOffers(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.status = :offered')
            ->andWhere('q.offerExpiresAt <= :now')
            ->setParameter('offered', QueueEntry::STATUS_OFFERED)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
