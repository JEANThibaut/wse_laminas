<?php
namespace Application\Service\Factory;

use Application\Service\UserAuthService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Doctrine\ORM\EntityManager;
use Application\Service\UserManager;

class UserAuthServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $entityManager = $container->get(EntityManager::class);
        $authenticationService = $container->get(AuthenticationService::class);
        $userManager = $container->get(UserManager::class);

        return new UserAuthService($entityManager, $authenticationService, $userManager);
    }
}
