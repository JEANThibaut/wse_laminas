<?php

namespace Parties\Controller\Factory;

use Parties\Controller\PartieController;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;

class PartieControllerFactory
{
    public function __invoke(ContainerInterface $container): PartieController
    {
        $entityManager = $container->get(EntityManager::class);
        return new PartieController($entityManager);
    }
}
