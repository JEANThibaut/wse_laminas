<?php

namespace User\Service;

use User\Entity\User;
use Doctrine\ORM\EntityManager;
use Laminas\Crypt\Password\Bcrypt;

class UserManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function addUser($data)
    {
        $bcrypt = new Bcrypt();
        $newHashedPassword = $bcrypt->create($data['password']);
        $dateString = $data['birthday'];
        $birthdate = new \DateTime($dateString);
        $user = new User();
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setBirthdate($birthdate);
        $user->setNickname($data['nickname']);
        $user->setEmail($data['email']);
        $user->setPassword($newHashedPassword);
        $user->setRoles('["user"]');
        $user->setIsMember(0);
        $user->setIsAdmin(0);
        $user->setIsBlacklist(0);
        $user->setIsActive(1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser($user ,$data)
    {
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setBirthdate($data['birthday']);
        $this->entityManager->flush();
        return $user;
    }
}