<?php

namespace Forum\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Forum\Entity\Forum;


class ForumController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $ForumManager;


    public function __construct($entityManager, $authService, $ForumManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->ForumManager = $ForumManager;

    }

    public function forumIndexAction(){
  
        $view = new ViewModel([
        
        ]);

        $this->layout()->setVariable('activeMenu', 'Forum-index');
        $view->setTemplate('forum/forum-index');
        return $view;
    }
 
}
