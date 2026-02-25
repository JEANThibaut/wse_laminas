<?php
namespace User\Controller;

use Application\Service\AuthService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\UserManager;
use User\Entity\User;

class UserController extends AbstractActionController
{   
    private $entityManager;
    private $userManager;
    private $authService;
    public function __construct($entityManager,  UserManager $userManager, AuthService $authService)
    {
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
        $this->authService = $authService;
    }




    public function usersAction()
    {
        $currentUser = $this->authService->getIdentity();
        $users = $this->entityManager->getRepository(User::class)->findBy([], ['lastname' => 'ASC']);
        $view = new ViewModel([
            
            'currentUser'=>$currentUser,
            'users'=>$users
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-users');
        $view->setTemplate('admin/users');
        // $view->setTerminal(true); 
        return $view;
    }

    
     public function editUserAction()
    {        
        $currentUser = $this->authService->getIdentity();
        $request = $this->getRequest();
        $params = $this->params()->fromRoute();
        $iduser = (int) $params['iduser'];
        if($request->isPost()){
            $data = $request->getPost()->toArray();
            // dump($data);
            if(!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['email']) ){
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
                if($user){
                   
                    $user->setFirstName($data['first_name']);
                    $user->setLastName($data['last_name']);
                    $user->setEmail($data['email']);
                    $user->setIsAdmin($data['isAdmin']);
                    $user->setIsMember($data['isMember']);
                     $user->setIsBlacklist($data['isBlacklist'] ?? 0);
                    $this->entityManager->flush();
                    $this->flashMessenger()->addSuccessMessage('Utilisateur modifié avec succès.');
                    return $this->redirect()->toRoute('admin-users');
                }else{
                    $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
                }
            }else{
                $this->flashMessenger()->addErrorMessage('Veuillez remplir tous les champs.');
            }
        }       
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
        $view = new ViewModel([
            'currentUser'=>$currentUser,
            'user'=>$user
        ]);
        
        $this->layout()->setVariable('activeMenu', 'admin-users');
        $view->setTemplate('admin/edit-user');
        return $view;
    }
  
    public function deleteUserAction()
    {        
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-users');
        }   
        $iduser = (int) $this->params()->fromPost('iduser');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
        if($user){
            $user->setIsActive(0);
            $this->entityManager->flush();
            $this->flashMessenger()->addSuccessMessage('Utilisateur supprimé avec succès.');
        }else{
            $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
        }
        return $this->redirect()->toRoute('admin-users');
    }
}
