<?php
namespace Game\Service;

use Game\Entity\Game;
use Game\Entity\Register;
use Doctrine\ORM\EntityManager;

class GameManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addGame(array $data)
    {

           $date = \DateTime::createFromFormat('d/m/Y', $data['date']);
            $newGame = new Game;
            $newGame->setDate($date);
            $newGame->setPlayerMax($data['player_max']);
            $newGame->setStatus((int)$data['status']);
            $this->entityManager->persist($newGame);
            $this->entityManager->flush();
            return $newGame;
    }


    public function editGame($game, array $data)
    {
            $date = \DateTime::createFromFormat('d/m/Y', $data['date']);
            $game->setDate($date);
            $game->setPlayerMax($data['player_max']);
            $game->setStatus((int)$data['status']);
            $this->entityManager->flush();
            return $game;
    }

    public function deleteGame( $game)
    {
        $this->entityManager->remove($game);
        $this->entityManager->flush();
        return true;
    }

   public function registerInGame($game, $currentUser)
{
    $existing = $this->entityManager->getRepository(Register::class)->findOneBy([
        'user_id' => $currentUser->getIdUser(),
        'game_id' => $game->getIdgame()
    ]);

    if ($existing) {
        return false; 
    }

    $register = new Register();
    $register->setUserId($currentUser->getIdUser());
    $register->setGameId($game->getIdgame());
    $register->setPresence(1); 
    $register->setPaid(0);   
    $register->setMember($currentUser->getIsMember());

    $this->entityManager->persist($register);
    $this->entityManager->flush();

    return true;
}
}
