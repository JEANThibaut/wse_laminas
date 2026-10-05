<?php
namespace Application\Service\Factory;

use Application\Service\PwaAccessPolicy;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PwaAccessPolicyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config')['pwa'] ?? [];

        // Sans config explicite, on reste en mode restreint
        return new PwaAccessPolicy((bool) ($config['restricted'] ?? true));
    }
}
