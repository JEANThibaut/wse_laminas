<?php

namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\Register;
use Game\Entity\WaitingList;
use Game\Form\GameForm;

class GameController extends AbstractActionController
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


    public function registerInGameAction(){
        $currentUser = $this->authService->getIdentity();
        $id = (int) $this->params()->fromQuery('id'); 
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame'=>$id]);
            if (!$game) {
            $this->flashMessenger()->addErrorMessage('Pas de partie trouvée');
            return $this->redirect()->toRoute('home');
        }
        $register = $this->gameManager->registerInGame($game,$currentUser);
        if($register){
            $this->flashMessenger()->addSuccessMessage('Votre inscription à bien été prise en compte');
        }
        else{
            $this->flashMessenger()->addErrorMessage('Une erreur est survenue');

        }
       
      return $this->redirect()->toRoute('home');
    }

    
    
    public function unregisterInGameAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $currentUser = $this->authService->getIdentity();
            $id = $this->params()->fromPost('id');
            $register = $this->entityManager->getRepository(Register::class)->findOneBy(['idregister'=>$id]);
            if($register){
                $this->entityManager->remove($register);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('Désinscription réussie.');
            }
            else{
                $this->flashMessenger()->addSuccessMessage('Une erreur est survenue.');
            }
        }
            

        return $this->redirect()->toRoute('home');
    }


    public function registerInWaitingListAction(){
        $currentUser = $this->authService->getIdentity();
        $id = (int) $this->params()->fromQuery('id'); 
        $waitingList  = $this->entityManager->getRepository(WaitingList::class)->findBy(['game_id'=>$id]);
         $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame'=>$id]);
        $count = count($waitingList);
        $newWaitingList = $this->gameManager->registerInWaitingList($currentUser,$game, $count);

       
      return $this->redirect()->toRoute('home');
    }

  
}
