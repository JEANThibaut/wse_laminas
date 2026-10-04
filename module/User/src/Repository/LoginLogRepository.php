<?php
namespace User\Repository;

use Doctrine\ORM\EntityRepository;

class LoginLogRepository extends EntityRepository
{
    // Duree de conservation des logs (RGPD : les IP sont des donnees personnelles)
    public const RETENTION = '-6 months';

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
     * Supprime les logs plus anciens que la duree de conservation.
     */
    public function purgeExpired(): int
    {
        return $this->getEntityManager()->createQuery(
            'DELETE FROM User\Entity\LoginLog l WHERE l.createdAt < :limit'
        )
            ->setParameter('limit', new \DateTime(self::RETENTION))
            ->execute();
    }
}
