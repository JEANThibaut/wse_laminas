<?php

namespace Application\Service\Factory;

use Application\Service\SumUpService;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SumUpServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        // Récupération des dépendances
        $authService = $container->get(AuthenticationService::class);
        $config = $container->get('config');
        $sumUpConfig = $config['sumup_settings'] ?? [];

        return new SumUpService($authService, $sumUpConfig);
    }
}