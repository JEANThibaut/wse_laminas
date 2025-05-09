<?php
namespace Application\Service;

use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationService;
use User\Entity\User;

class AuthService
{
    private EntityManager $entityManager;
    private AuthenticationService $authenticationService;

    public function __construct(EntityManager $entityManager, AuthenticationService $authenticationService)
    {
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
    }

    public function login(string $email, string $password): bool
    {
        // Cherche l'utilisateur par email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return false;
        }   

        if (password_verify($password, $user->getPassword())) {
            $this->authenticationService->getStorage()->write($user);
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        $this->authenticationService->clearIdentity();
    }

    public function getIdentity()
    {
        return $this->authenticationService->getIdentity();
    }
}
