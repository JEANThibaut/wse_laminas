<?php
namespace Annonce\Controller\Factory;

use Annonce\Controller\AnnonceController;
use Annonce\Service\AnnonceManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;


class AnnonceControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $annonceManager = $container->get(AnnonceManager::class);
        return new AnnonceController($entityManager,$authService,$annonceManager);
    }
}
