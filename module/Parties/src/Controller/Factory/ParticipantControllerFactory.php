<?php

namespace Parties\Controller\Factory;

use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Parties\Controller\ParticipantController;

class ParticipantControllerFactory
{
    public function __invoke(ContainerInterface $container): ParticipantController
    {
        $entityManager = $container->get(EntityManager::class);
        return new ParticipantController($entityManager);
    }
}
