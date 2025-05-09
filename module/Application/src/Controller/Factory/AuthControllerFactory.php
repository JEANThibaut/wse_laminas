<?php
namespace Application\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\Controller\AuthController;
use Application\Service\UserAuthService;
use Application\Service\UserManager;
use Doctrine\ORM\EntityManager;

class AuthControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $userAuthService = $container->get(UserAuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $userManager = $container->get(UserManager::class);
        return new AuthController($userAuthService, $entityManager, $userManager);
    }
}
