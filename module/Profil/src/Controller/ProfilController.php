<?php

namespace Profil\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Profil\Entity\Replique;
use User\Entity\User;
use Game\Entity\Register;

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
        // $form = new \Profil\Form\RepliqueForm();
        $registers = $this->entityManager->getRepository(Register::class)->findBy(['user'=>$currentUser]);
  
        $view = new ViewModel([
            // 'form' => $form,
            'currentUser'=>$currentUser,
            'registers'=>$registers,
        ]);
        $this->layout()->setVariable('activeMenu', 'profil');
        $view->setTemplate('profil/profil');
        return $view;

    }


    // public function profilAction()
    // {

    //     $currentUser = $this->authService->getIdentity();
   
    //     // $form = new \Profil\Form\RepliqueForm();
    //     $view = new ViewModel([
    //         // 'form' => $form,
    //         'currentUser'=>$currentUser,
    //     ]);
    //     $this->layout()->setVariable('activeMenu', 'profil');
    //     $view->setTemplate('profil/profil');
    //     $view->setTerminal(true); 
    //     return $view;

    // }

    public function profilEditAction()
    {
        $request = $this->getRequest();
        $currentUser = $this->authService->getIdentity();

        $userId = (int) $this->params()->fromRoute('id');
        $user = $this->entityManager->getRepository(User::class)->find($userId);


        if (!$user) {
            return $this->notFoundAction();
        }

        if (!$currentUser->getIsAdmin() && $currentUser->getIdUser() !== $user->getIdUser()) {
            $this->flashMessenger()->addErrorMessage("Accès refusé.");
            return $this->redirect()->toRoute('home');
        }

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            $user->setFirstName($data['firstname']);
            $user->setLastName($data['lastname']);
            $user->setNickname($data['nickname']);
            $user->setEmail($data['email']);
            $user->setBirthdate(new \DateTime($data['birthdate']));

            $this->entityManager->flush();

            // ✅ Si l'utilisateur modifié est celui connecté → on met à jour la session
            if ($currentUser->getIdUser() === $user->getIdUser()) {
                $this->authService->getStorage()->write($user);
            }

            $this->flashMessenger()->addSuccessMessage("Profil mis à jour.");
            return $this->redirect()->toRoute('profil-index');
        }

        $view = new ViewModel([
            'user' => $user  // ici on affiche le profil de l'utilisateur ciblé
        ]);
        $this->layout()->setVariable('activeMenu', 'profil');
        $view->setTemplate('profil/profil-edit');

        return $view;
    }


    
        
    public function arsenalAction()
    {
        $currentUser = $this->authService->getIdentity();
        $repliques = $this->entityManager->getRepository(Replique::class)->findBy(['iduser'=>$currentUser->getIdUser()]);
        $view = new ViewModel([
            'currentUser'=>$currentUser,
            'repliques'=>$repliques,
        ]);
        $this->layout()->setVariable('activeMenu', 'arsenal');
        $view->setTemplate('profil/arsenal');
        return $view;

    }



    public function updateRepliqueAction()
    {
        $request = $this->getRequest();
        $currentUser = $this->authService->getIdentity();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $action = $request->getPost('action');
            $data['puissance'] = str_replace(',', '.', $data['puissance']);
            if ($action === 'edit') {
                $replique = $this->entityManager->getRepository(Replique::class)->findOneBy(['idreplique'=>$data['replique-id']]);
                if ($replique) {
                    $this->repliqueManager->updateReplique($replique, $data);
                    $this->flashMessenger()->addSuccessMessage('Réplique mise à jour.');
                } 
            }
            elseif($action === 'add'){
                
                $newReplique = $this->repliqueManager->addReplique($data, $currentUser);
                $this->flashMessenger()->addSuccessMessage('Réplique ajoutée.');
            }
            elseif ($action === 'delete') {
                $replique = $this->entityManager->getRepository(Replique::class)->find($data['replique-id']);
                $this->entityManager->remove($replique);
                $this->flashMessenger()->addSuccessMessage('Réplique supprimé.');
            }
        }
        return $this->redirect()->toRoute('arsenal');
    }


  



}
