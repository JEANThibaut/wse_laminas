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

    public function annoncesIndexAction(){
                if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
                    $this->flashMessenger()->addErrorMessage('Accès refusé.');
                    return $redirect;
                }
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

    public function getAnnonceAction(){
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }

        $currentUser = $this->authService->getIdentity();
        $annonceId = (int) $this->params()->fromRoute('id');
        $annonce = $this->entityManager->getRepository(Annonce::class)->findOneBy(['idannonce'=>$annonceId]);
        dump($annonce);
        $view = new ViewModel([
            'annonce' => $annonce,
        ]);

        $this->layout()->setVariable('activeMenu', 'annonce-index');
        $view->setTemplate('annonce/get-annonce');
        return $view;
    }


  
    
  
}
