<?php
namespace Application\View\Helper\Factory;

use Application\Service\PushService;
use Application\View\Helper\PushPublicKey;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PushPublicKeyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new PushPublicKey($container->get(PushService::class));
    }
}
