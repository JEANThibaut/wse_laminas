<?php

namespace Annonce\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Annonce\Entity\Annonce;


class AnnonceController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $annonceManager;


    public function __construct($entityManager, $authService, $annonceManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->annonceManager = $annonceManager;

    }

    public function annoncesAction(){
        


        $view = new ViewModel([

        ]);

        $this->layout()->setVariable('activeMenu', 'annonce-index');
        $view->setTemplate('annonce/annonce-index');
        return $view;
    }
    
  
}
