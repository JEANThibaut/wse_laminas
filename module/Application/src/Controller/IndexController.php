<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Service\UserAuthService;

class IndexController extends AbstractActionController
{
    private $userAuthService;

    public function __construct(UserAuthService $userAuthService)
    {
        // Vérifier si UserAuthService est bien injecté
        var_dump($userAuthService);  // Affiche l'objet UserAuthService
        exit;  // Arrêter ici pour vérifier
    }

    public function indexAction()
    {
        // Ton code habituel ici
    }
}

