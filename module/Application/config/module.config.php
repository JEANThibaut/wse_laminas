<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Authentication\Storage\Session;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\Session as SessionStorage;
return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'register' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/register',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'register',
                    ],
                ],
            ],
            'login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'login',
                    ],
                ],
            ],
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
        ],
    ],
'controllers' => [
    'factories' => [
        Controller\IndexController::class => Controller\Factory\IndexControllerFactory::class,
        Controller\RegisterController::class => InvokableFactory::class,
        Controller\AuthController::class => Controller\Factory\AuthControllerFactory::class,
    ],
],
    'controller_plugins' => [
        'factories' => [
            Plugin\ValidateApiKey::class => InvokableFactory::class,
        ],
        'aliases' => [
            'validateApiKey' => Plugin\ValidateApiKey::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Application\Service\UserAuthService::class => Application\Service\Factory\UserAuthServiceFactory::class,
            Application\Service\UserManager::class => Application\Service\Factory\UserManagerFactory::class,
            Laminas\Authentication\AuthenticationService::class => function ($container) {
                $storage = new Laminas\Authentication\Storage\Session('UserAuth');
                return new Laminas\Authentication\AuthenticationService($storage);
            },
        ],
        'aliases' => [
            'authentication' => Laminas\Authentication\AuthenticationService::class,
            'UserAuthService' => Application\Service\UserAuthService::class,  // Ajoute cet alias
        ],
    ],

    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
