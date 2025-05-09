<?php

declare(strict_types=1);

namespace User;

use Laminas\Mvc\Application;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Application\Middleware\CorsMiddleware;

return [
    'router' => [
        'routes' => [

            'users' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/users[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'get-all-users' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/users/get-all-users[/:order][/:direction]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'getAllUsers',
                    ],
                ],
            ],
            'get-user' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/users/get-user/:id',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'getUser',
                    ],
                ],
            ],
            'add-user' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/users/add-user',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'addUser',
                    ],
                ],
            ],
            'update-user' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/users/update-user',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'updateUser',
                    ],
                ],
            ],
            'migrate-users' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/migrate-users',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'migrateUsers',
                    ],
                ],
            ],

            'get-user-info' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/users/get-user-info',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'getUserInfo',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Controller\Factory\IndexControllerFactory::class,
            Controller\AuthController::class => Controller\Factory\AuthControllerFactory::class,
        ],
    ],
    'middleware_pipeline' => [
        'always' => [
            'middleware' => [
                \Application\Middleware\CorsMiddleware::class,
            ],
            'priority' => 10000,
        ],
    ],
    'service_manager' => [
        'aliases' => [
            'CorsMiddleware' => \Application\Middleware\CorsMiddleware::class,
        ],
        'factories' => [
            \Application\Middleware\CorsMiddleware::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            Service\UsersManager::class => Service\Factory\UsersManagerFactory::class,
          
        ],
    ],
    'view_manager' => [
        // Activer les détails d'erreurs et d'exceptions uniquement en développement
        'display_not_found_reason' => true,
        'display_exceptions'       => true,

        // Type de document HTML
        'doctype'                  => 'HTML5',

        // Chemin pour les templates de vue spécifiques à ce module
        'template_path_stack' => [
            __DIR__ . '/../view', // Chemin vers les vues de ce module
        ],

        // Gestion des réponses JSON
        'strategies' => [
            'ViewJsonStrategy', // Active la stratégie pour les réponses JSON
        ],
    ],

];
