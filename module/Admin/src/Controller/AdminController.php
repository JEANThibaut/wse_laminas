<?php

namespace Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Form\GameForm;
use User\Entity\User;

class AdminController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $gameManager;

    public function __construct($entityManager, $authService, $gameManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->gameManager = $gameManager;
    }


    public function indexAction()
    {
        $this->layout()->setVariable('activeMenu', 'admin-index');
        $view = new ViewModel();
        $view->setTemplate('admin/index');
        return $view;

    }


    public function gamesAction()
    {
        $currentUser = $this->authService->getIdentity();
        $form = new GameForm();
        $view = new ViewModel([
            'form' => $form,
            'currentUser'=>$currentUser,
        ]);
        $this->layout()->setVariable('activeMenu', 'game');
        $view->setTemplate('admin/games');
        $view->setTerminal(true); 
        return $view;
    }


    public function nextGameAction()
    {
        $currentUser = $this->authService->getIdentity();
        $request = $this->getRequest();

        $form = new GameForm();

        if ($request->isPost()) {
            $form->setData($request->getPost());
        
            if ($form->isValid()) {
                $data = $form->getData();
                dump($data);

                // return $this->redirect()->toRoute('admin-games',);
            }
        }


        $view = new ViewModel([
            'form' => $form,
            'currentUser'=>$currentUser,
        ]);
        $this->layout()->setVariable('activeMenu', 'game');
        $view->setTemplate('admin/next-game');
        $view->setTerminal(true); 
        return $view;
    }


    public function usersAction()
    {
        $currentUser = $this->authService->getIdentity();
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $view = new ViewModel([
            
            'currentUser'=>$currentUser,
            'users'=>$users
        ]);
        $this->layout()->setVariable('activeMenu', 'game');
        $view->setTemplate('admin/users');
        $view->setTerminal(true); 
        return $view;
    }
  
}
