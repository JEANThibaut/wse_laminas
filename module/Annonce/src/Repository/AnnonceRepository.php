<?php
namespace Annonce\Repository;

use Doctrine\ORM\EntityRepository;


class AnnonceRepository extends EntityRepository
{

 public function fetchPaginated(int $page = 1, int $perPage = 10, string $search = null): array
{
    $qb = $this->createQueryBuilder('a')
        ->orderBy('a.date', 'DESC')
        ->setFirstResult(($page - 1) * $perPage)
        ->setMaxResults($perPage);

    if (!empty($search)) {
        $qb->andWhere('a.titre LIKE :search OR a.description LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    return $qb->getQuery()->getResult();
}
}
