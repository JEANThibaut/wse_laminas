<?php
namespace User\Repository;

use Doctrine\ORM\EntityRepository;

class LoginLogRepository extends EntityRepository
{
    /**
     * Derniers evenements de connexion, les plus recents d'abord. Le terme
     * filtre sur l'email saisi, le nom, le prenom ou l'IP ; l'etat sur un
     * LoginLog::STATE_* (vide : tous).
     */
    public function findLatest(string $term = '', string $state = '', int $limit = 200): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit);

        $term = trim($term);
        if ($term !== '') {
            // % et _ saisis sont cherches litteralement
            $like = '%' . addcslashes(mb_strtolower($term), '%_\\') . '%';
            $qb->andWhere('LOWER(l.email) LIKE :term OR LOWER(u.firstname) LIKE :term OR LOWER(u.lastname) LIKE :term OR l.ip LIKE :term')
                ->setParameter('term', $like);
        }
        if ($state !== '') {
            $qb->andWhere('l.state = :state')->setParameter('state', $state);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre d'evenements dans les etats donnes depuis une date.
     *
     * @param string[] $states LoginLog::STATE_*
     */
    public function countSince(array $states, \DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.state IN (:states)')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('states', $states)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
