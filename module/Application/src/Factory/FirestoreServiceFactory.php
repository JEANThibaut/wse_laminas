<?php

namespace Application\Factory;

use Application\Service\FirestoreService;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FirestoreServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new FirestoreService(); 
    }
}
