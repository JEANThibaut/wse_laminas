<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\LoginForm;
use Application\Service\AuthService;
use User\Entity\User;
use Application\Form\RegisterForm;

class AuthController extends AbstractActionController
{
    private AuthService $authService;
    private $entityManager;
    private $userManager;
    public function __construct(AuthService $authService, $entityManager, $userManager)
    {

        $this->authService = $authService;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
    }



    public function loginAction()
    {
        $message = null;

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password)) {
                if ($this->authService->login($email, $password)) {
                    $this->flashMessenger()->addSuccessMessage("Connexion réussie.");
                    return $this->redirect()->toRoute('home');
                } else {
                    $message = "Identifiants incorrects.";
                }
            } else {
                $message = "Veuillez remplir tous les champs correctement.";
            }
        }

        $this->layout()->setVariable('activeMenu', 'login');

        return new ViewModel([
            'message' => $message,
        ]);
    }

    public function logoutAction()
    {
        $this->authService->logout();
        $this->flashMessenger()->addSuccessMessage("Déconnexion réussie.");
        return $this->redirect()->toRoute('home');
    }

    public function resetPasswordAction()
    {   
        
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $email = trim($data['email'] ?? '');
            $newPassword = trim($data['new-password'] ?? '');
                if($newPassword !=""){
                    $reset = $this->authService->resetPassword($email, $newPassword);
                    if ($reset) {
                        $this->flashMessenger()->addSuccessMessage("Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.");
                        return $this->redirect()->toRoute('login');
                    } else {
                        $this->flashMessenger()->addErrorMessage("Erreur lors de la réinitialisation du mot de passe. Le token peut être invalide ou expiré.");
                    }
                }else{
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->authService->sendPasswordResetLink($email);
                        $this->flashMessenger()->addSuccessMessage("Si cet email est enregistré, un lien de réinitialisation a été envoyé.");
                        return $this->redirect()->toRoute('home');
                    } else {
                        $this->flashMessenger()->addErrorMessage("Veuillez entrer une adresse email valide.");
                    } 
                }
        } else {
            if ($token = $this->params()->fromQuery('token')) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
                if ($user) {
                    return new ViewModel([
                        'user' => $user,
                    ]);
                } else {
                    $this->flashMessenger()->addErrorMessage("Token invalide ou expiré.");
                    return new ViewModel();
                }
            }
        }
        return new ViewModel();
    }


     public function registerAction()
    {   
        // $form = new RegisterForm();
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
         
            // dump($data);
            $mail = trim($data['email'] ?? '');
            $firstname = trim($data['firstname'] ?? '');
            $lastname = trim($data['lastname'] ?? '');
            $nickname = trim($data['nickname'] ?? '');
            $password = trim($data['password'] ?? '');
            $confirmPassword = trim($data['confirm_password'] ?? '');
            $birthday_day = trim($data['birthday_day'] ?? '');
            $birthday_month = trim($data['birthday_month'] ?? '');
            $birthday_year = trim($data['birthday_year'] ?? '');
            $birthday = sprintf('%04d-%02d-%02d', $birthday_year, $birthday_month, $birthday_day);
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $mail]);

        
            if ($user) {
                $this->flashMessenger()->addErrorMessage("Cette adresse email est déjà utilisée.");
            }
            elseif($password !== $confirmPassword) {
                $this->flashMessenger()->addErrorMessage("Les mots de passe ne correspondent pas.");
            }
            else{
                $newUser = $this->userManager->addUser([
                    'email' => $mail,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'nickname' => $nickname,
                    'password' => $password,
                    'birthday' => $birthday,
                ]);
                if(!$newUser) {
                    $this->flashMessenger()->addErrorMessage("Erreur lors de la création de l'utilisateur.");
                }
                // Connexion automatique
                if ($this->authService->login($mail, $password)) {
                    $this->flashMessenger()->addSuccessMessage("Inscription et connexion réussies.");
                    return $this->redirect()->toRoute('home');
                } else {
                    $message = "Inscription réussie mais connexion impossible.";
                }
    
            }
        }
        return new ViewModel(
        
        );
    }
}
