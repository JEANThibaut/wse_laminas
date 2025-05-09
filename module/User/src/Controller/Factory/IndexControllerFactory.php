<?php

namespace User\Controller\Factory;

use User\Controller\IndexController;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use User\Service\UsersManager;
use Laminas\Authentication\AuthenticationService;

class IndexControllerFactory
{
    public function __invoke(ContainerInterface $container): IndexController
    {
        $entityManager = $container->get(EntityManager::class);
        $usersManager = $container->get(UsersManager::class);

        return new IndexController($entityManager, $usersManager);
    }
}
