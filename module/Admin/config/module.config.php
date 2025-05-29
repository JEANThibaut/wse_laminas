<?php
namespace Admin;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Admin\Controller\AdminController;
use Admin\Controller\AjaxController;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;

return [
    'router' => [
        'routes' => [
            // 'admin-index' => [
            //     'type' => Literal::class,
            //     'options' => [
            //         'route' => '/admin-index',
            //         'defaults' => [
            //             'controller' => AdminController::class,
            //             'action' => 'index',
            //         ],
            //     ],
            // ],
            'admin-games' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/games',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'games',
                    ],
                ],
            ],
            'admin-edit-game' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/admin/admin-edit-game/[:id]',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'editGame',
                    ],
                ],
            ],
            'admin-delete-game' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/admin/admin-delete-game',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'deleteGame',
                    ],
                ],
            ],
            'admin-next-games' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/next-game',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'nextGame',
                    ],
                ],
            ],
            'admin-users' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/users',
                    'defaults' => [
                        'controller' => AdminController::class,
                        'action' => 'users',
                    ],
                ],
            ],
            'admin-get-users' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin-get-users',
                    'defaults' => [
                        'controller' => AjaxController::class,
                        'action' => 'getUsers',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\AdminController::class => Controller\Factory\AdminControllerFactory::class, 
            Controller\AjaxController::class => Controller\Factory\AjaxControllerFactory::class,

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
            Game\Service\GameManager::class => Gaame\Service\Factory\GameManagerFactory::class,
        ],
    ],
    // 'template_path_stack' => [
    //     'Game' => __DIR__ . '/../view',
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
                'Game\Entity' => 'Profil_entity',
            ],
        ],
    ],
],
];

