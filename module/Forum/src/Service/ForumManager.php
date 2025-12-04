<?php
namespace Forum\Service;

use Forum\Entity\Forum;

use User\Entity\User;
use Doctrine\ORM\EntityManager;

class ForumManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

 

}
