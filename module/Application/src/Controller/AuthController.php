<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\LoginForm;
use Application\Service\UserAuthService;
use Doctrine\ORM\EntityManager;
use Application\Service\UserManager;

class AuthController extends AbstractActionController
{
    private $authService;
    private $entityManager;
    private $userManager;

    public function __construct(UserAuthService $authService, EntityManager $entityManager, UserManager $userManager)
    {
        $this->authService = $authService;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
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
                    return $this->redirect()->toRoute('home');
                } else {
                    $message = "Identifiants incorrects.";
                }
            }
        }

        return new ViewModel([
            'form' => $form,
            'message' => $message,
        ]);
    }

    public function logoutAction()
    {
        $this->authService->logout();
        return $this->redirect()->toRoute('home');
    }
}


