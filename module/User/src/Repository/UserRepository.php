<?php
namespace User\Repository;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public const SCOPE_PLAYERS = 'players';
    public const SCOPE_MEMBERS = 'members';
    public const SCOPE_INACTIVE = 'inactive';

    /**
     * Tous les comptes actifs dont l'email n'est pas valide, quel que soit leur role.
     */
    public function findUnvalidatedActive(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isActive IS NULL OR u.isActive <> 0')
            ->andWhere('u.mailValidation = false')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre de comptes actifs et, parmi eux, de comptes a l'email valide.
     *
     * @return array{total: int, validated: int}
     */
    public function countMailValidation(): array
    {
        $row = $this->createQueryBuilder('u')
            ->select('COUNT(u.iduser) AS total')
            ->addSelect('SUM(CASE WHEN u.mailValidation = true THEN 1 ELSE 0 END) AS validated')
            ->where('u.isActive IS NULL OR u.isActive <> 0')
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $row['total'],
            'validated' => (int) $row['validated'],
        ];
    }

    /**
     * Comptes actifs dont l'email n'est pas valide, hors membres, admins et GOD :
     * ceux que la desactivation des comptes non valides doit viser.
     */
    public function findUnvalidatedToDeactivate(): array
    {
        $users = $this->createQueryBuilder('u')
            ->where('u.isActive IS NULL OR u.isActive <> 0')
            ->andWhere('u.mailValidation = false')
            ->andWhere('u.member = false')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();

        // Les droits admin/GOD se lisent aussi dans le JSON roles : meme regle que partout
        return array_values(array_filter($users, fn ($user) => !$user->hasAdminAccess()));
    }

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
