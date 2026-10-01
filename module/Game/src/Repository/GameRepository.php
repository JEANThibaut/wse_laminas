<?php
namespace Game\Repository;

use Doctrine\ORM\EntityRepository;
use Game\Entity\GameRegister;

class GameRepository extends EntityRepository
{

    public function findActiveGames(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = 1')
            ->orderBy('g.date', 'ASC')
            ->getQuery()
            ->getResult();
    }


public function findNextGame()
{
    return $this->createQueryBuilder('g')
        ->where('g.date >= :today')
        ->setParameter('today', new \DateTime('today'))
        ->orderBy('g.date', 'ASC')
        ->andWhere('g.status = 1')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}

public function findRegister($game, $currentUser){
        return $this->_em->getRepository(GameRegister::class)->findOneBy([
        'game' => $game->getIdGame(),
    'user' => $currentUser,
    'status' => GameRegister::STATUS_ACTIVE,
    ]);
}

public function findUsersByGameId(int $gameId): array
{
    return $this->createQueryBuilder('r')
        ->where('r.game_id = :gameId')
        ->setParameter('gameId', $gameId)
        ->getQuery()
        ->getResult();
}

public function getNextArrivedNumber($excludedRegister, $gameId)
{
    return $this->createQueryBuilder('r')
        ->select('MAX(r.arrived_number)')
        ->where('r.game = :gameId')
        ->andWhere('r != :excludedRegister')
        ->setParameters([
            'gameId' => $gameId,
            'excludedRegister' => $excludedRegister,
        ])
        ->getQuery()
        ->getSingleScalarResult();
}

public function getFirstMissingArrivedNumber($excludedRegister, $gameId): int
{
    $results = $this->createQueryBuilder('r')
        ->select('r.arrived_number')
        ->where('r.game = :gameId')
        ->andWhere('r != :excludedRegister')
        ->andWhere('r.arrived_number > 0')
        ->orderBy('r.arrived_number', 'ASC')
        ->setParameters([
            'gameId' => $gameId,
            'excludedRegister' => $excludedRegister,
        ])
        ->getQuery()
        ->getArrayResult();

    $usedNumbers = array_column($results, 'arrived_number');

    $expected = 1;
    foreach ($usedNumbers as $num) {
        if ($num != $expected) {
            return $expected;
        }
        $expected++;
    }

    return $expected;
}

/**
 * Participation par utilisateur sur les parties deja passees, hors
 * desinscriptions : [iduser => ['registered' => n, 'validated' => n]].
 * Une inscription est validee quand un numero d'arrivee lui a ete attribue.
 */
public function getParticipationStatsByUser(): array
{
    $rows = $this->_em->createQueryBuilder()
        ->select('IDENTITY(r.user) AS iduser')
        ->addSelect('COUNT(r.idregister) AS registered')
        ->addSelect('SUM(CASE WHEN r.arrived_number > 0 THEN 1 ELSE 0 END) AS validated')
        ->from(GameRegister::class, 'r')
        ->join('r.game', 'g')
        ->where('r.status = :status')
        ->andWhere('g.date < :today')
        ->setParameter('status', GameRegister::STATUS_ACTIVE)
        ->setParameter('today', new \DateTime('today'))
        ->groupBy('r.user')
        ->getQuery()
        ->getArrayResult();

    $stats = [];
    foreach ($rows as $row) {
        $stats[(int) $row['iduser']] = [
            'registered' => (int) $row['registered'],
            'validated' => (int) $row['validated'],
        ];
    }
    return $stats;
}


}
