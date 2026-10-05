<?php
namespace Application\Controller\Factory;

use Application\Controller\PushController;
use Application\Service\AuthService;
use Application\Service\PushService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PushControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new PushController(
            $container->get(AuthService::class),
            $container->get(PushService::class)
        );
    }
}
