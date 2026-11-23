<?php
namespace Annonce\Repository;

use Annonce\Entity\Annonce;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use User\Entity\User;

class AnnonceRepository extends EntityRepository
{
    /**
     * Annonces en ligne et non expirees, les plus recentes d'abord, filtrees
     * par texte (titre ou description) et par tag.
     */
    public function searchOnline(int $page, int $perPage, string $search = '', ?int $tagId = null): Paginator
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :online')
            ->andWhere('a.expiresAt > :now')
            ->setParameter('online', Annonce::STATUS_ONLINE)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.createdAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($search !== '') {
            $qb->andWhere('a.title LIKE :search OR a.description LIKE :search')
                ->setParameter('search', '%' . addcslashes($search, '%_\\') . '%');
        }
        if ($tagId) {
            $qb->innerJoin('a.tags', 'filterTag', 'WITH', 'filterTag.id = :tagId')
                ->setParameter('tagId', $tagId);
        }

        return new Paginator($qb->getQuery(), true);
    }

    /**
     * @return Annonce[] annonces du membre encore visibles par lui (en ligne,
     *                   expirees ou retirees mais pas encore purgees)
     */
    public function findByOwner(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Annonces comptant dans le quota : en ligne et non expirees, ou en
     * attente de validation (sinon on pourrait en deposer sans limite).
     */
    public function countOnlineByOwner(User $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.user = :user')
            ->andWhere('(a.status = :online AND a.expiresAt > :now) OR a.status = :pending')
            ->setParameter('user', $user)
            ->setParameter('online', Annonce::STATUS_ONLINE)
            ->setParameter('pending', Annonce::STATUS_PENDING)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Annonce[] annonces expirees ou retirees avant $before, a purger
     */
    public function findPurgeable(\DateTime $before): array
    {
        return $this->createQueryBuilder('a')
            ->where('(a.status = :online AND a.expiresAt < :before) OR (a.status <> :online AND a.closedAt < :before)')
            ->setParameter('online', Annonce::STATUS_ONLINE)
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();
    }
}
