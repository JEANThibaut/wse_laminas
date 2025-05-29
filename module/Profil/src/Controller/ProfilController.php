<?php

namespace Profil\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Profil\Entity\Replique;

class ProfilController extends AbstractActionController
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


    public function indexAction()
    {

        $currentUser = $this->authService->getIdentity();
        $form = new \Profil\Form\RepliqueForm();
        $view = new ViewModel([
            'form' => $form,
            'currentUser'=>$currentUser,
        ]);
        $this->layout()->setVariable('activeMenu', 'profil');
        $view->setTemplate('profil/profil');
        return $view;

    }


    public function profilAction()
    {

        $currentUser = $this->authService->getIdentity();
        $form = new \Profil\Form\RepliqueForm();
        $view = new ViewModel([
            'form' => $form,
            'currentUser'=>$currentUser,
        ]);
        $this->layout()->setVariable('activeMenu', 'profil');
        $view->setTemplate('profil/profil');
        $view->setTerminal(true); 
        return $view;

    }

    
    public function arsenalAction()
    {

        $currentUser = $this->authService->getIdentity();
        $form = new \Profil\Form\RepliqueForm();
        $view = new ViewModel([
            'form' => $form,
            'currentUser'=>$currentUser,
        ]);
        $this->layout()->setVariable('activeMenu', 'profil');
        $view->setTemplate('profil/arsenal');
        // $view->setTerminal(true); 
        return $view;

    }



    public function updateRepliqueAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
    
            $data['puissance'] = str_replace(',', '.', $data['puissance']);
    
            $replique = $this->repliqueManager->getRepliqueById($data['id']);
            if ($replique) {
                $this->repliqueManager->updateReplique($replique, $data);
                $this->flashMessenger()->addSuccessMessage('Réplique mise à jour.');
            } else {
                $this->flashMessenger()->addErrorMessage('Réplique introuvable.');
            }
        }
    
        return $this->redirect()->toRoute('profil');
    }

  



}
