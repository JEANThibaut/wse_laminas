<?php
namespace Application\View\Helper\Factory;

use Application\View\Helper\PwaAccess;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PwaAccessFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config')['pwa'] ?? [];

        // Sans config explicite, on reste en mode restreint
        return new PwaAccess(
            (bool) ($config['restricted'] ?? true),
            (array) ($config['allowed_emails'] ?? [])
        );
    }
}
