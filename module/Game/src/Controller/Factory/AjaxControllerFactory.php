<?php
namespace Game\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Game\Controller\AjaxController;
use Game\Service\RepliqueManager;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;

class AjaxControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $repliqueManager = $container->get(RepliqueManager::class);
        return new AjaxController($entityManager, $authService, $repliqueManager);
    }
}
