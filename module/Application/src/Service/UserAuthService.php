<?php 
namespace Application\Service;

use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationService;
use User\Entity\User;

class UserAuthService
{
    private $entityManager;
    private $authenticationService;
    private $userManager;

    public function __construct(EntityManager $entityManager, AuthenticationService $authenticationService, UserManager $userManager)
    {
        // Injection des dépendances
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
        $this->userManager = $userManager;
    }

    public function login(string $email, string $password): bool
    {
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
