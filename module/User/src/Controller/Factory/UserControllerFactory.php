<?php
namespace User\Controller\Factory;

use Application\Service\AuthService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Controller\UserController;
use User\Service\UserManager;

class UserControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {   
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $userManager = $container->get(UserManager::class);
        $authService = $container->get(AuthService::class);
        return new UserController($entityManager,$userManager, $authService);
    }
}
