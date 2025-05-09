<?php

namespace Forum\Controller\Factory;

use Forum\Controller\IndexController;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;

class IndexControllerFactory
{
    public function __invoke(ContainerInterface $container): IndexController
    {
        $entityManager = $container->get(EntityManager::class);
        return new IndexController($entityManager);
    }
}
