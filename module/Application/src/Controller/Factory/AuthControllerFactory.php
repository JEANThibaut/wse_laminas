<?php

namespace Application\Controller\Factory;

use Application\Controller\AuthController;
use Application\Service\AuthService;
use Psr\Container\ContainerInterface;
use User\Service\UserManager;

class AuthControllerFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $userManager = $container->get(UserManager::class);
        return new AuthController($authService, $entityManager, $userManager);
    }
}
