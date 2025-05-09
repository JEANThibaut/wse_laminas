<?php
namespace Application\Service\Factory;

use Application\Service\AuthService;
use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        // Récupère EntityManager et AuthenticationService depuis le container
        $entityManager = $container->get(EntityManager::class);
        $authenticationService = $container->get(AuthenticationService::class);

        return new AuthService($entityManager, $authenticationService);
    }
}
