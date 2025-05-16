<?php
namespace Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;



class AjaxController extends AbstractActionController
{
    private $authService;
    private $entityManager;

    public function __construct($entityManager,$authService)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;

    }

    public function getUsersAction()
    {
        $users = $this->entityManager->getRepository(\User\Entity\User::class)->findAll();
    
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getIduser(),
                'lastname' => $user->getLastname(),
                'firstname' => $user->getFirstname(),
            ];
        }
    
        return new JsonModel(['data' => $data]);
    }

}
