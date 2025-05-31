<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\LoginForm;
use Application\Service\AuthService;

class AuthController extends AbstractActionController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
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
}
