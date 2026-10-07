<?php
namespace Application\View\Helper\Factory;

use Application\Service\FeatureAccess as FeatureAccessService;
use Application\View\Helper\FeatureAccess;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FeatureAccessFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new FeatureAccess($container->get(FeatureAccessService::class));
    }
}
