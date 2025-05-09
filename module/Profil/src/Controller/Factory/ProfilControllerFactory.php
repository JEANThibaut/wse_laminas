<?php
namespace Profil\Controller\Factory;

use Profil\Controller\ProfilController;
use Profil\Service\RepliqueManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;


class ProfilControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $repliqueManager = $container->get(RepliqueManager::class);
        return new ProfilController($entityManager,$authService,$repliqueManager);
    }
}
