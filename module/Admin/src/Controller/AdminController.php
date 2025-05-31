<?php

namespace Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
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


    public function gamesAction()
    {
        $currentUser = $this->authService->getIdentity();
        $request = $this->getRequest();
        $games = $this->entityManager->getRepository(Game::class)->findAll();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            if (!empty($data['date']) && !empty($data['player_max']) && isset($data['status'])) {

                $newGame = $this->gameManager->addGame($data);
                if($newGame){
                    $this->flashMessenger()->addSuccessMessage('La partie a bien été ajoutée.');
                    return $this->redirect()->toRoute('admin-games');
                }
                $this->flashMessenger()->addErrorMessage('Une erreur est survenu.');
                return $this->redirect()->toRoute('admin-games');
            }
        }
        $view = new ViewModel([
            "games"=>$games,
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-games');
        $view->setTemplate('admin/games');
        return $view;
    }


    public function editGameAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $game = $this->entityManager->getRepository(Game::class)->find($id);

        if (!$game) {
            $this->flashMessenger()->addErrorMessage('Partie introuvable.');
            return $this->redirect()->toRoute('admin-games');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if (!empty($data['date']) && !empty($data['player_max']) && isset($data['status'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $data['date']);
                if ($date) {
                    $game = $this->gameManager->editGame($game, $data);
                    if($game){
                        $this->flashMessenger()->addSuccessMessage('La partie a bien été modifiée.');
                        return $this->redirect()->toRoute('admin-games');
                    }else{
                          $this->flashMessenger()->addErrorMessage('Une erreur est survenu.');
                    }

                } else {
                    $this->flashMessenger()->addErrorMessage('Format de date invalide.');
                }
            }
        }

        $view = new ViewModel([
            "game"=>$game,
        ]);
        $view->setTemplate('admin/edit-game');
        return $view;
    }

    public function deleteGameAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id =  $request->getPost('id');
            $game = $this->entityManager->getRepository(Game::class)->find($id);
            if ($game) {
                $delete = $this->gameManager->deleteGame($game);
                $this->flashMessenger()->addSuccessMessage('Partie supprimée avec succès.');
            } else {
                $this->flashMessenger()->addErrorMessage('Partie introuvable.');
            }
        }

        return $this->redirect()->toRoute('admin-games');
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

                // return $this->redirect()->toRoute('home',);
            }
        }


        $view = new ViewModel([
            'form' => $form,
            'currentUser'=>$currentUser,
        ]);
        $this->layout()->setVariable('activeMenu', 'game');
        $view->setTemplate('admin/next-game');
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
        $this->layout()->setVariable('activeMenu', 'admin-users');
        $view->setTemplate('admin/users');
        // $view->setTerminal(true); 
        return $view;
    }
  
}
