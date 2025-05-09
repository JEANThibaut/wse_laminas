<?php
namespace User;

use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\Factory\InvokableFactory;
use User\Controller\AuthController;

return [
    'router' => [
        'routes' => [
            // 'login' => [
            //     'type' => Literal::class,
            //     'options' => [
            //         'route' => '/login',
            //         'defaults' => [
            //             'controller' => AuthController::class,
            //             'action' => 'login',
            //         ],
            //     ],
            // ],
        ],
    ],
    // 'controllers' => [
    //     'factories' => [
    //         Controller\AuthController::class => Controller\Factory\AuthControllerFactory::class, 
    //     ],
    // ],
    // 'service_manager' => [
    //     'factories' => [
    //         Service\AuthService::class => Service\Factory\AuthServiceFactory::class,
    //     ],
    // ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view',
        ],
    ],
    'doctrine' => [
    'driver' => [
        'user_entity' => [
            'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
            'cache' => 'array',
            'paths' => [__DIR__ . '/../src/Entity'],
        ],
        'orm_default' => [
            'drivers' => [
                'User\Entity' => 'user_entity',
            ],
        ],
    ],
],
];

