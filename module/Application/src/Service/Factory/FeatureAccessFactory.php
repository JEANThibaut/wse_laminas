<?php
namespace Application\Service\Factory;

use Application\Service\FeatureAccess;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FeatureAccessFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        // Sans config, tout est ferme
        return new FeatureAccess($container->get('config')['features'] ?? []);
    }
}
