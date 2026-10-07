<?php
namespace Game\Service\Factory;

use Application\Service\AuthService;
use Application\Service\FeatureAccess;
use Application\Service\PushService;
use Doctrine\ORM\EntityManager;
use Game\Service\GameManager;
use Game\Service\QueueManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class QueueManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config')['queue'] ?? [];

        return new QueueManager(
            $container->get(EntityManager::class),
            $container->get(GameManager::class),
            $container->get(PushService::class),
            $container->get(AuthService::class),
            // Qui voit et rejoint la file : config features.queue
            $container->get(FeatureAccess::class),
            // Sans config explicite, propositions manuelles (par un admin)
            (bool) ($config['auto_offer'] ?? false),
            (string) ($config['site_url'] ?? 'https://www.wolfsofteure.fr')
        );
    }
}
