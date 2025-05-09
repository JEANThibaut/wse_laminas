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
        $form = new LoginForm();
        $message = null;

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);

            if ($form->isValid()) {
                $email = $data['email'];
                $password = $data['password'];

                if ($this->authService->login($email, $password)) {
                    $this->flashMessenger()->addSuccessMessage("Connexion réussie.");
                    return $this->redirect()->toRoute('home');
                } else {
                    $message = "Identifiants incorrects.";
                }
            }
        }
        $this->layout()->setVariable('activeMenu', 'login');
        return new ViewModel([
            'form'    => $form,
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
