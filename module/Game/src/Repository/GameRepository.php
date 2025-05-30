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
        'game_id' => $game->getIdgame(),
        'user_id' => $currentUser->getIduser()
    ]);
}

}
