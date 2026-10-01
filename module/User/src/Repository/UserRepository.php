<?php
namespace User\Repository;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * Utilisateurs dont le nom, le prenom ou l'email contient le terme
     * (insensible a la casse), tries par nom. Terme vide : tous les utilisateurs.
     * Un compte est desactive uniquement si isActive vaut explicitement 0.
     */
    public function search(string $term, bool $active = true): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        if ($active) {
            $qb->where('u.isActive IS NULL OR u.isActive <> 0');
        } else {
            $qb->where('u.isActive = 0');
        }

        $term = trim($term);
        if ($term !== '') {
            // % et _ saisis par l'utilisateur sont cherches litteralement
            $like = '%' . addcslashes(mb_strtolower($term), '%_\\') . '%';
            $qb->andWhere('LOWER(u.lastname) LIKE :term OR LOWER(u.firstname) LIKE :term OR LOWER(u.email) LIKE :term')
                ->setParameter('term', $like);
        }

        return $qb->getQuery()->getResult();
    }
}
