<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use Game\Entity\WaitingList;
use Actus\Entity\Actus;
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
        if($game){
            $countRegister = $this->entityManager->getRepository(GameRegister::class)->findBy([
                'game' => $game->getIdGame(),
                'status' => GameRegister::STATUS_ACTIVE,
            ]);
        }   
        $this->layout()->setVariable('activeMenu', 'home');
        $register = null;
        if($game && $currentUser){
            $register = $this->entityManager->getRepository(Game::class)->findRegister($game,$currentUser->getIdUser());
            $countRegister = $this->entityManager->getRepository(GameRegister::class)->findBy([
                'game' => $game->getIdGame(),
                'status' => GameRegister::STATUS_ACTIVE,
            ]);
            // $isInWaitingList = $this->entityManager->getRepository(WaitingList::class)->findOneBy(['game'=>$game->getIdGame(),'user'=>$currentUser->getIdUser()]);
            if($register){
                $isRegister = true;
            }
            $isComplete = count($countRegister) >= $game->getPlayerMax();
        }

        $actus = $this->entityManager->getRepository(Actus::class)->findBy(
            ['isActive' => 1],
            ['date' => 'DESC'],
            2
        );


        return new ViewModel([
            'game' => $game,
            'isRegister'=> $isRegister,
            'currentUser'=>$currentUser,
            'register'=>$register ?? null,
            'isComplete'=>$isComplete,
            'actus' => $actus,
            'countRegister'=> $countRegister ?? null,
            // 'isInWaitingList'=>$isInWaitingList,
        ]);
    }

    public function faqAction()
    {
        $this->layout()->setVariable('activeMenu', 'faq');
        return new ViewModel();
    }

    public function briefingAction()
    {
        $this->layout()->setVariable('activeMenu', 'briefing');
        return new ViewModel();
    }
}
