<?php
namespace Game\Repository;

use Doctrine\ORM\EntityRepository;
use Game\Entity\Register;

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
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}

public function findRegister($game, $currentUser){
        return $this->_em->getRepository(Register::class)->findOneBy([
        'game' => $game->getIdGame(),
        'user' => $currentUser
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


}
