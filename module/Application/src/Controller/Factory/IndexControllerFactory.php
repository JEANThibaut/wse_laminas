<?php

namespace Application\Controller\Factory;

use Psr\Container\ContainerInterface;
use Application\Controller\IndexController;

class IndexControllerFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new IndexController(); 
    }
}
