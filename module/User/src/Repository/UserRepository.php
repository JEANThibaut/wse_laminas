<?php
namespace User\Repository;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public const SCOPE_PLAYERS = 'players';
    public const SCOPE_MEMBERS = 'members';
    public const SCOPE_INACTIVE = 'inactive';

    /**
     * Utilisateurs dont le nom, le prenom ou l'email contient le terme
     * (insensible a la casse), tries par nom. Terme vide : tous les utilisateurs.
     * Un compte est desactive uniquement si isActive vaut explicitement 0.
     *
     * @param string $scope self::SCOPE_* : comptes actifs non membres, membres
     *                      actifs, ou comptes desactives (membres ou non)
     */
    public function search(string $term, string $scope = self::SCOPE_PLAYERS): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        if ($scope === self::SCOPE_INACTIVE) {
            $qb->where('u.isActive = 0');
        } else {
            $qb->where('u.isActive IS NULL OR u.isActive <> 0')
                ->andWhere('u.member = :member')
                ->setParameter('member', $scope === self::SCOPE_MEMBERS);
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
