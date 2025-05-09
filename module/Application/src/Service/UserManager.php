<?php

namespace Application\Service;

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
        $user = new User();
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setBirthday($data['birthday']);
        $user->setNickname($data['nickname']);
        $user->setEmail($data['email']);
        $user->setPassword($newHashedPassword);
        $user->setRole('["user"]');
        $user->setBlacklist(0);
        $user->setAvatar('');
        $user->setGotKey(0);
        $user->setGotRemote(0);
        $user->setIsActive(1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser($user ,$data)
    {
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setBirthday($data['birthday']);
 
        $this->entityManager->flush();

        return $user;
    }
}