<?php
namespace Application\Controller\Factory;

use Application\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;
class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);

        return new IndexController($authService, $entityManager);
    }
}
