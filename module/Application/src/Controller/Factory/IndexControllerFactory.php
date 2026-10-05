<?php
namespace Application\Controller\Factory;

use Application\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;
use Game\Service\GameManager;
use Game\Service\QueueManager;
class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);

        return new IndexController(
            $authService,
            $entityManager,
            $container->get(GameManager::class),
            $container->get(QueueManager::class)
        );
    }
}
