<?php
namespace Actus\Controller\Factory;

use Actus\Controller\ActusController;
use Actus\Service\ActusManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;


class ActusControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $actusManager = $container->get(ActusManager::class);
        return new ActusController($entityManager, $authService, $actusManager);
    }
}
