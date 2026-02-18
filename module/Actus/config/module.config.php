<?php
namespace Actus;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;
use Actus\Controller\ActusController;
// use Actus\Controller\AjaxController;
// use Actus\Service\ActusManager;


return [
    'router' => [
        'routes' => [
            'actus-index' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/actus-index',
                    'defaults' => [
                        'controller' => ActusController::class,
                        'action' => 'actusIndex',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ActusController::class => Controller\Factory\ActusControllerFactory::class, 

        ],
    ],
     'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    
    'service_manager' => [
        'factories' => [
            Application\Service\AuthService::class =>  Application\Service\Factory\AuthServiceFactory::class,
            Service\ActusManager::class => Service\Factory\ActusManagerFactory::class,
        ],
    ],
    // 'template_path_stack' => [
    //     'Annonce' => __DIR__ . '/../view',
    // ],
    'doctrine' => [
    'driver' => [
        'Profil_entity' => [
            'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
            'cache' => 'array',
            'paths' => [__DIR__ . '/../src/Entity'],
        ],
        'orm_default' => [
            'drivers' => [
                'Actus\Entity' => 'Profil_entity',
            ],
        ],
    ],
],
];

