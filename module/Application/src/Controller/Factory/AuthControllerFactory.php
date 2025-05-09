<?php

namespace Application\Controller\Factory;

use Application\Controller\AuthController;
use Application\Service\AuthService;
use Psr\Container\ContainerInterface;

class AuthControllerFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        return new AuthController($authService);
    }
}
