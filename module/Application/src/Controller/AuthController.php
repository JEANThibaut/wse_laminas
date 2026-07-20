<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\LoginForm;
use Application\Service\AuthService;
use User\Entity\User;
use Application\Form\RegisterForm;
use Application\Util\InputSanitizer;

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

            $email = InputSanitizer::cleanString($data['email'] ?? '');
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
            $email = InputSanitizer::cleanString($data['email'] ?? '');
            $newPassword = trim($data['new-password'] ?? '');
            $confirmPassword = trim($data['confirm-password'] ?? '');
            $token = InputSanitizer::cleanString($data['token'] ?? '');
                if($newPassword !=""){
                    if ($newPassword !== $confirmPassword) {
                        $this->flashMessenger()->addErrorMessage("Les mots de passe ne correspondent pas.");
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
                        return new ViewModel(['user' => $user]);
                    }
                    $reset = $this->authService->resetPassword($email, $newPassword);
                    if ($reset) {
                        $this->flashMessenger()->addSuccessMessage("Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.");
                        return $this->redirect()->toRoute('login');
                    } else {
                        $this->flashMessenger()->addErrorMessage("Erreur lors de la réinitialisation du mot de passe. Le token peut être invalide ou expiré.");
                    }
                }elseif($token !== ''){
                    // Formulaire "nouveau mot de passe" (token present) soumis sans mot de passe saisi
                    $this->flashMessenger()->addErrorMessage("Veuillez saisir un nouveau mot de passe.");
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
                    return new ViewModel(['user' => $user]);
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
            $token = InputSanitizer::cleanString($this->params()->fromQuery('token'));
            if ($token !== '') {
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

            $mailRaw = $data['email'] ?? '';
            $firstnameRaw = $data['firstname'] ?? '';
            $lastnameRaw = $data['lastname'] ?? '';
            $nicknameRaw = $data['nickname'] ?? '';

            $mail = InputSanitizer::cleanString($mailRaw);
            $firstname = InputSanitizer::cleanString($firstnameRaw);
            $lastname = InputSanitizer::cleanString($lastnameRaw);
            $nickname = InputSanitizer::cleanString($nicknameRaw);
            $password = trim($data['password'] ?? '');
            $confirmPassword = trim($data['confirm_password'] ?? '');
            $birthday_day = (int) ($data['birthday_day'] ?? 0);
            $birthday_month = (int) ($data['birthday_month'] ?? 0);
            $birthday_year = (int) ($data['birthday_year'] ?? 0);

            $errors = [];
            if ($mail !== $mailRaw || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Veuillez entrer une adresse email valide.";
            }
            if ($firstname !== $firstnameRaw || $firstname === '' || !preg_match('/\A[\p{L}\p{M}\'\-\s]+\z/u', $firstname)) {
                $errors[] = "Le prenom contient des caracteres invalides.";
            }
            if ($lastname !== $lastnameRaw || $lastname === '' || !preg_match('/\A[\p{L}\p{M}\'\-\s]+\z/u', $lastname)) {
                $errors[] = "Le nom contient des caracteres invalides.";
            }
            if ($nickname !== '') {
                if ($nickname !== $nicknameRaw || !preg_match('/\A[\p{L}\p{M}0-9._\'\-\s]+\z/u', $nickname)) {
                    $errors[] = "Le pseudo contient des caracteres invalides.";
                }
            }
            if (!checkdate($birthday_month, $birthday_day, $birthday_year)) {
                $errors[] = "La date de naissance est invalide.";
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->flashMessenger()->addErrorMessage($error);
                }
                return new ViewModel();
            }

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
