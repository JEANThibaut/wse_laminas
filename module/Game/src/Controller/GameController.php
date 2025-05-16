<?php

namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Replique;
use Game\Form\GameForm;

class GameController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $repliqueManager;

    public function __construct($entityManager, $authService, $repliqueManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->repliqueManager = $repliqueManager;
    }


    // public function adminGamesAction()
    // {

    //     $currentUser = $this->authService->getIdentity();
    //     $form = new GameForm();
    //     $view = new ViewModel([
    //         'form' => $form,
    //         'currentUser'=>$currentUser,
    //     ]);
    //     $this->layout()->setVariable('activeMenu', 'game');
    //     $view->setTemplate('game/admin-games');
    //     return $view;

    // }

  
}
