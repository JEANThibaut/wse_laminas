<?php
namespace User\Repository;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * Utilisateurs dont le nom, le prenom ou l'email contient le terme
     * (insensible a la casse), tries par nom. Terme vide : tous les utilisateurs.
     */
    public function search(string $term): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        $term = trim($term);
        if ($term !== '') {
            // % et _ saisis par l'utilisateur sont cherches litteralement
            $like = '%' . addcslashes(mb_strtolower($term), '%_\\') . '%';
            $qb->where('LOWER(u.lastname) LIKE :term')
                ->orWhere('LOWER(u.firstname) LIKE :term')
                ->orWhere('LOWER(u.email) LIKE :term')
                ->setParameter('term', $like);
        }

        return $qb->getQuery()->getResult();
    }
}
