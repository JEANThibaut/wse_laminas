<?php
namespace Application\Service\Factory;

use Application\Service\PushService;
use Application\Service\FeatureAccess;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PushServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        // Cles VAPID : config/autoload/global.php, jamais dans le repo
        $vapid = $container->get('config')['push']['vapid'] ?? [];

        return new PushService(
            $container->get(EntityManager::class),
            $container->get(FeatureAccess::class),
            $vapid
        );
    }
}
