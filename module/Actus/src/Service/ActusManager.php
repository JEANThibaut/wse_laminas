<?php
namespace Actus\Service;

use Actus\Entity\Actus;

use User\Entity\User;
use Doctrine\ORM\EntityManager;

class ActusManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

 

}
