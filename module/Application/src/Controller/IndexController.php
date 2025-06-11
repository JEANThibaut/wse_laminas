<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\Register;
use Game\Entity\WaitingList;
class IndexController extends AbstractActionController
{
    private $authService;
    private $entityManager;

    public function __construct($authService, $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
    }


       public function indexAction()
    {
        $currentUser = $this->authService->getIdentity();
        $game = $this->entityManager->getRepository(Game::class)->findNextGame();
        $isRegister = false;
         $isInWaitingList = false;
         $isComplete = false;
         $this->layout()->setVariable('activeMenu', 'home');
        if($game && $currentUser){
            $register = $this->entityManager->getRepository(Game::class)->findRegister($game,$currentUser);
            $countRegister = $this->entityManager->getRepository(Register::class)->findBy(['game_id'=>$game->getIdgame(),]);
            $isInWaitingList = $this->entityManager->getRepository(WaitingList::class)->findOneBy(['game_id'=>$game->getIdgame(),'user_id'=>$currentUser->getIdUser()]);
          if($register){
            $isRegister = true;
          }
            $isComplete = count($countRegister) >= $game->getPlayerMax();
        }

       
        return new ViewModel([
            'game' => $game,
            'isRegister'=> $isRegister,
            'currentUser'=>$currentUser,
            'register'=>$register ?? null,
            'isComplete'=>$isComplete,
            'isInWaitingList'=>$isInWaitingList,
        ]);
    }
}
