<?php
namespace Annonce\Service;

use Annonce\Entity\Annonce;

use User\Entity\User;
use Doctrine\ORM\EntityManager;

class AnnonceManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

 

}
