<?php

namespace User\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * Trouver tous les utilisateurs triés par un champ
     */
    public function findAllOrdered($field , $direction )
    {   
        return $this->createQueryBuilder('u') 
            ->orderBy('u.' . $field, $direction) 
            ->getQuery()
            ->getResult();
    }
}
