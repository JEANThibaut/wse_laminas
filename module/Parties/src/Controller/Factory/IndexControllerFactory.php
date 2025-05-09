<?php

namespace Parties\Controller\Factory;

use Parties\Controller\IndexController;
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
