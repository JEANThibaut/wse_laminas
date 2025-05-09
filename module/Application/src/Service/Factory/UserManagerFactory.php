<?php
namespace Application\Service\Factory;

use Application\Service\UserManager;
use Psr\Container\ContainerInterface;

class UserManagerFactory
{
    public function __invoke(ContainerInterface $container): UserManager
    {
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        return new UserManager($entityManager);
    }
}
