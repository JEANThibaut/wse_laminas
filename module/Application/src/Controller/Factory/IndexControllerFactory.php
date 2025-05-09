<?php
namespace Application\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\Controller\IndexController;
use Application\Service\UserAuthService;
use Doctrine\ORM\EntityManager;
use Application\Service\UserManager;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        // Récupération des services depuis le conteneur
        $userAuthService = $container->get(UserAuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $userManager = $container->get(UserManager::class);

        // Retourner une nouvelle instance de IndexController avec ses dépendances injectées
        return new IndexController($userAuthService, $entityManager, $userManager);
    }
}
