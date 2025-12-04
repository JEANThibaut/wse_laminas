<?php
namespace Forum\Controller\Factory;

use Forum\Controller\ForumController;
use Forum\Service\ForumManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Application\Service\AuthService;


class ForumControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $authService = $container->get(AuthService::class);
        $entityManager = $container->get(EntityManager::class);
        $ForumManager = $container->get(ForumManager::class);
        return new ForumController($entityManager,$authService,$ForumManager);
    }
}
