<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use Game\Entity\WaitingList;
use Actus\Entity\Actus;
use Game\Service\GameManager;
class IndexController extends AbstractActionController
{
    private $authService;
    private $entityManager;
    private GameManager $gameManager;

    public function __construct($authService, $entityManager, GameManager $gameManager)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->gameManager = $gameManager;
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
        $queue = null;
        if($game && $currentUser){
            // Inscription en cours, y compris en file d'attente
            $register = $this->entityManager->getRepository(GameRegister::class)->findCurrentRegister($game, $currentUser);
            $countRegister = $this->entityManager->getRepository(GameRegister::class)->findBy([
                'game' => $game->getIdGame(),
                'status' => GameRegister::STATUS_ACTIVE,
            ]);
            // $isInWaitingList = $this->entityManager->getRepository(WaitingList::class)->findOneBy(['game'=>$game->getIdGame(),'user'=>$currentUser->getIdUser()]);
            if($register){
                $isRegister = true;
            }
            $isComplete = count($countRegister) >= $game->getPlayerMax();

            // File d'attente : le joueur y est deja, ou y serait place en s'inscrivant
            $isPending = $register && $register->isPending();
            if ($isPending || (!$register && $this->gameManager->mustQueue($currentUser))) {
                $opening = $this->gameManager->getConfirmationOpening($game, $currentUser);
                $now = new \DateTimeImmutable('now', $opening->getTimezone());
                $queue = [
                    'isPending' => $isPending,
                    'opening' => $opening,
                    'isOpen' => $now >= $opening && $now < $this->gameManager->getGameStart($game),
                ];
            }
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
            'queue' => $queue,
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
