<?php

namespace Actus\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Actus\Entity\Actus;


class ActusController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $actusManager;


    public function __construct($entityManager, $authService, $actusManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->actusManager = $actusManager;

    }

    public function actusIndexAction(){
        // Récupère toutes les actus (simple implémentation)
        $actus = $this->entityManager->getRepository(Actus::class)->findAll();

        $view = new ViewModel([
            'actus' => $actus,
        ]);

        $this->layout()->setVariable('activeMenu', 'actus-index');
        $view->setTemplate('actus/actus-index');
        return $view;
    }   
  
}
