<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\Register;
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
         $this->layout()->setVariable('activeMenu', 'home');
        if($game && $currentUser){
            $register = $this->entityManager->getRepository(Game::class)->findRegister($game,$currentUser);
            $countRegister = $this->entityManager->getRepository(Register::class)->findBy(['game_id'=>$game->getIdgame(),]);
        
          if($register){
            $isRegister = true;
          }
        }
        $isComplete = count($countRegister) >= $game->getPlayerMax();
        return new ViewModel([
            'game' => $game,
            'isRegister'=> $isRegister,
            'currentUser'=>$currentUser,
            'register'=>$register ?? null,
            'isComplete'=>$isComplete,
        ]);
    }
}
