<?php
namespace User;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use User\Controller\UserController;

return [
    'router' => [
        'routes' => [
            'admin-users' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/users',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'users',
                    ],
                ],
            ],
            'admin-edit-user' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/admin/edit-user/[:iduser]',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'editUser',
                    ],
                ],
            ],
            'admin-delete-user' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/admin/delete-user',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'deleteUser',
                    ],
                ],
            ],
  

        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\UserController::class => Controller\Factory\UserControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\UserManager::class => Service\Factory\UserManagerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'User_entity' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'User\Entity' => 'User_entity',
                ],
            ],
        ],
    ],
];
