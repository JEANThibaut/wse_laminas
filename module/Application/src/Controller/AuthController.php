<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\LoginForm;
use Application\Service\AuthService;
use User\Entity\User;
use Application\Form\RegisterForm;
use Application\Util\InputSanitizer;
use Laminas\Session\Container;

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
                if ($this->authService->login($email, $password, $this->getRequest())) {
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
        $this->authService->logout($this->getRequest());
        $this->flashMessenger()->addSuccessMessage("Déconnexion réussie.");
        return $this->redirect()->toRoute('home');
    }

    public function resetPasswordAction()
    {   
        
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $token = InputSanitizer::cleanString($data['token'] ?? '');

            // Formulaire "nouveau mot de passe" : seul le token recu par mail
            // identifie le compte
            if ($token !== '') {
                $user = $this->authService->findUserByResetToken($token);
                if (!$user) {
                    $this->flashMessenger()->addErrorMessage("Ce lien de réinitialisation est invalide ou a expiré. Veuillez en demander un nouveau.");
                    return $this->redirect()->toRoute('reset-password');
                }

                $newPassword = trim($data['new-password'] ?? '');
                $confirmPassword = trim($data['confirm-password'] ?? '');
                $message = null;
                if (mb_strlen($newPassword) < AuthService::PASSWORD_MIN_LENGTH) {
                    $message = "Le mot de passe doit contenir au moins " . AuthService::PASSWORD_MIN_LENGTH . " caractères.";
                } elseif ($newPassword !== $confirmPassword) {
                    $message = "Les mots de passe ne correspondent pas.";
                } elseif ($this->authService->resetPassword($token, $newPassword)) {
                    $this->flashMessenger()->addSuccessMessage("Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.");
                    return $this->redirect()->toRoute('login');
                } else {
                    $message = "Erreur lors de la réinitialisation du mot de passe.";
                }

                return new ViewModel([
                    'user' => $user,
                    'message' => $message,
                ]);
            }

            // Formulaire "demande de lien"
            $email = InputSanitizer::cleanString($data['email'] ?? '');
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->authService->sendPasswordResetLink($email);
                $this->flashMessenger()->addSuccessMessage("Si cet email est enregistré, un lien de réinitialisation a été envoyé.");
                return $this->redirect()->toRoute('home');
            }
            $this->flashMessenger()->addErrorMessage("Veuillez entrer une adresse email valide.");
        } else {
            $token = InputSanitizer::cleanString($this->params()->fromQuery('token'));
            if ($token !== '') {
                $user = $this->authService->findUserByResetToken($token);
                if ($user) {
                    return new ViewModel([
                        'user' => $user,
                    ]);
                }
                $this->flashMessenger()->addErrorMessage("Ce lien de réinitialisation est invalide ou a expiré. Veuillez en demander un nouveau.");
            }
        }
        return new ViewModel();
    }


    // Delai minimum entre deux envois du lien de validation depuis l'accueil
    private const VALIDATION_RESEND_DELAY = 60;

    /**
     * Encart de l'accueil : corrige eventuellement l'adresse puis envoie le
     * lien de validation a l'adresse du compte.
     */
    public function sendValidationEmailAction()
    {
        $user = $this->authService->getIdentity();
        if (!$user) {
            return $this->redirect()->toRoute('login');
        }
        if (!$this->getRequest()->isPost() || $user->isMailValidated()) {
            return $this->redirect()->toRoute('home');
        }

        $session = new Container('EmailValidation');
        if (time() - (int) ($session->lastSent ?? 0) < self::VALIDATION_RESEND_DELAY) {
            $this->flashMessenger()->addErrorMessage('Un lien vient de vous être envoyé. Patientez une minute avant de le redemander.');
            return $this->redirect()->toRoute('home');
        }

        $email = InputSanitizer::cleanString($this->params()->fromPost('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flashMessenger()->addErrorMessage('Veuillez entrer une adresse email valide.');
            return $this->redirect()->toRoute('home');
        }
        if ($this->authService->isEmailTakenByAnother($email, $user)) {
            $this->flashMessenger()->addErrorMessage('Cette adresse email est déjà utilisée par un autre compte.');
            return $this->redirect()->toRoute('home');
        }
        // Une nouvelle adresse remplace l'ancienne (et reste a valider)
        $user->setEmail($email);
        $this->entityManager->flush();

        if ($this->sendValidationLink($user)) {
            $session->lastSent = time();
            $this->flashMessenger()->addSuccessMessage('Un lien de validation a été envoyé à ' . $user->getEmail() . '. Pensez à vérifier vos spams.');
        } else {
            $this->flashMessenger()->addErrorMessage("L'envoi du mail a échoué. Réessayez dans quelques minutes.");
        }
        return $this->redirect()->toRoute('home');
    }

    private function sendValidationLink(User $user): bool
    {
        $result = $this->authService->sendEmailValidationLinks(
            [$user],
            fn (array $params) => $this->url()->fromRoute('validate-email', [], ['force_canonical' => true, 'query' => $params])
        );
        return $result['sent'] === 1;
    }

    /**
     * Arrivee du lien de validation envoye par mail.
     */
    public function validateEmailAction()
    {
        $user = $this->authService->findUserByEmailValidation(
            InputSanitizer::cleanInt($this->params()->fromQuery('u')),
            InputSanitizer::cleanInt($this->params()->fromQuery('e')),
            InputSanitizer::cleanString($this->params()->fromQuery('s'))
        );

        if (!$user) {
            $this->flashMessenger()->addErrorMessage("Ce lien de validation est invalide ou a expiré.");
            return $this->redirect()->toRoute('home');
        }

        $this->authService->markEmailValidated($user);
        $this->flashMessenger()->addSuccessMessage('Votre adresse email ' . $user->getEmail() . ' est validée. Merci !');
        return $this->redirect()->toRoute('home');
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
                } else {
                    // Tout nouveau compte doit valider son adresse : le lien part tout de suite
                    $this->sendValidationLink($newUser);
                }
                // Connexion automatique
                if ($this->authService->login($mail, $password, $this->getRequest(), true)) {
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
