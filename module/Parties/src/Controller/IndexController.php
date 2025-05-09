<?php

declare(strict_types=1);

namespace Parties\Controller;

use DateTime;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use Parties\Entity\Participants;
use Parties\Entity\Parties;
use Users\Entity\Users;

use function PHPSTORM_META\type;

// use Users\Entity\Users;

class IndexController extends AbstractActionController
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
}



