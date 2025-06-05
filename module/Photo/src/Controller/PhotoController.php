<?php

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;


class PhotoController extends AbstractActionController
{

    private $authService;
    private $entityManager;

    public function __construct($entityManager, $authService, )
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
    }


    public function indexAction()
    {

        $currentUser = $this->authService->getIdentity();
        $view = new ViewModel([
            'currentUser'=>$currentUser,
        ]);
        $this->layout()->setVariable('activeMenu', 'photos');
        $view->setTemplate('photo/photo-index');
        return $view;

    }


    



}
