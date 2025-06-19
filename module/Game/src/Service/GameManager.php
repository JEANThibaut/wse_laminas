<?php
namespace Game\Service;

use Game\Entity\Game;
use Game\Entity\Register;
use Game\Entity\WaitingList;
use User\Entity\User;
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
         $user = $this->entityManager->getRepository(User::class)->find($currentUser->getIdUser());

        $existing = $this->entityManager->getRepository(Register::class)->findOneBy([
            'user' => $user,
            'game' => $game
        ]);

        if ($existing) {
            return false; 
        }

        $register = new Register();
        $register->setUser($user);
        $register->setGame($game);
        $register->setPaid(0);   
        $register->setMember($user->getIsMember());
        $this->entityManager->persist($register);
        $this->entityManager->flush();

        return true;
    }

    public function registerInWaitingList($currentUser,$game, $count){
        
        $newWaitingList = new WaitingList;
        $newWaitingList->setGameId($game->getIdGame());
        $newWaitingList->setUserId($currentUser->getIdUser());
        $newWaitingList->setEmailSend(0);
        $newWaitingList->setIsValidate(0);
        $newWaitingList->setOrderList($count +1);
        $this->entityManager->persist($newWaitingList);
        $this->entityManager->flush();

    }

}
