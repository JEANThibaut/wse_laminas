<?php
namespace Profil\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Profil\Service\RepliqueManager;
use Profil\Entity\Replique;
use Profil\Form\RepliqueForm;


class AjaxController extends AbstractActionController
{
    private $authService;
    private $repliqueManager;
    private $entityManager;

    public function __construct($entityManager,$authService, $repliqueManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->repliqueManager = $repliqueManager;
    }

    
}
