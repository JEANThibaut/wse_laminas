<?php
namespace Photo\Controller\Factory;

use Photo\Controller\PhotoController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;


class PhotoControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);
     
        return new PhotoController($entityManager,$authService);
    }
}
