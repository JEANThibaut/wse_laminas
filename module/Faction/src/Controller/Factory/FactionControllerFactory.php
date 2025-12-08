<?php
namespace Faction\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Faction\Controller\FactionController;
use Faction\Service\FactionManager;
use Application\Service\AuthService;

class FactionControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {

        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $factionManager = $container->get(FactionManager::class);
        $authService = $container->get(AuthService::class);
        return new FactionController($factionManager,$authService,$entityManager);
    }
}
