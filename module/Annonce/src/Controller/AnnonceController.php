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
        $page = (int)$this->params()->fromQuery('page', 1);
        $search = $this->params()->fromQuery('search', '');
        $annonces = $this->entityManager->getRepository(Annonce::class)->fetchPaginated($page, 10, $search);

        $view = new ViewModel([
            'annonces' => $annonces,
            'page' => $page,
            'search' => $search,
        ]);

        $this->layout()->setVariable('activeMenu', 'annonce-index');
        $view->setTemplate('annonce/annonce-index');
        return $view;
    }


  
    
  
}
