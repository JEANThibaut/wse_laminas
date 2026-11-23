<?php
namespace Organisation\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Organisation\Entity\Post;

class PostRepository extends EntityRepository
{
    /**
     * Fil : epinglees d'abord, puis les plus recentes ; filtre facultatif par type.
     */
    public function feed(int $page, int $perPage, ?string $type = null): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.pinned', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);
        if ($type !== null && isset(Post::TYPES[$type])) {
            $qb->where('p.type = :type')->setParameter('type', $type);
        }
        return new Paginator($qb->getQuery());
    }

    /**
     * @return Post[] publications epinglees, les plus recentes d'abord (accueil)
     */
    public function pinned(int $limit = 5): array
    {
        return $this->findBy(['pinned' => true], ['createdAt' => 'DESC', 'id' => 'DESC'], $limit);
    }

    /**
     * @return Post[] evenements commencant dans l'intervalle [debut, fin[, par date
     */
    public function eventsBetween(\DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :event')
            ->andWhere('p.startsAt >= :from AND p.startsAt < :to')
            ->setParameter('event', Post::TYPE_EVENT)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('p.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Post[] prochains evenements (pas encore commences)
     */
    public function upcomingEvents(int $limit = 3): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :event')
            ->andWhere('p.startsAt >= :now')
            ->setParameter('event', Post::TYPE_EVENT)
            ->setParameter('now', new \DateTime())
            ->orderBy('p.startsAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
