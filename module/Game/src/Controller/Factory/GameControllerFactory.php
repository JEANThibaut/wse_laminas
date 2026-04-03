<?php
namespace Game\Controller\Factory;

use Application\Service\SumUpService;
use Game\Controller\GameController;
use Game\Service\GameManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;


class GameControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $gameManager = $container->get(GameManager::class);
        $sumupService = $container->get(SumUpService::class);
        $config = $container->get('config');
        $sumupConfig = $config['sumup_settings'] ?? [];

        return new GameController($entityManager, $authService, $gameManager, $sumupService, $sumupConfig);
    }
}
