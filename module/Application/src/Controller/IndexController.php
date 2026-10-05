<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use Actus\Entity\Actus;
use Game\Service\GameManager;
use Game\Service\QueueManager;
class IndexController extends AbstractActionController
{
    private $authService;
    private $entityManager;
    private GameManager $gameManager;
    private QueueManager $queueManager;

    public function __construct($authService, $entityManager, GameManager $gameManager, QueueManager $queueManager)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->gameManager = $gameManager;
        $this->queueManager = $queueManager;
    }


       public function indexAction()
    {
        $currentUser = $this->authService->getIdentity();
        $game = $this->entityManager->getRepository(Game::class)->findNextGame();
        $this->layout()->setVariable('activeMenu', 'home');

        $register = null;
        $registeredCount = 0;
        $isComplete = false;
        // File d'attente : visible seulement des comptes autorises (admins en phase de test)
        $queueAvailable = false;
        $queueStatus = null;
        if ($game) {
            $registeredCount = $this->entityManager->getRepository(GameRegister::class)->countActiveRegisters($game);
            // Complete aussi quand les places restantes sont proposees a la file
            $isComplete = $this->gameManager->isFull($game);

            if ($currentUser) {
                $register = $this->entityManager->getRepository(GameRegister::class)->findCurrentRegister($game, $currentUser);
                $queueAvailable = $this->queueManager->isAvailableFor($currentUser);
                if ($queueAvailable) {
                    $queueStatus = $this->queueManager->getStatus($game, $currentUser);
                }
            }
        }

        $actus = $this->entityManager->getRepository(Actus::class)->findBy(
            ['isActive' => 1],
            ['date' => 'DESC'],
            2
        );


        return new ViewModel([
            'game' => $game,
            'currentUser' => $currentUser,
            'register' => $register,
            'registeredCount' => $registeredCount,
            'isComplete' => $isComplete,
            'queueAvailable' => $queueAvailable,
            'queueStatus' => $queueStatus,
            'actus' => $actus,
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
