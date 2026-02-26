<?php
namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Authentication\Storage\Session;
use Laminas\Authentication\AuthenticationService;

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
            'login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'login',
                    ],
                ],
            ],
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'logout',
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
            'reset-password' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/reset-password',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'resetPassword',
                    ],
                ],
            ],
            'faq' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/faq',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'faq',
                    ],
                ],
            ],
               'briefing' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/briefing',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'briefing',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
      
            Controller\AuthController::class => Controller\Factory\AuthControllerFactory::class,
            Controller\IndexController::class => Controller\Factory\IndexControllerFactory::class,

            // Controller\IndexController::class => InvokableFactory::class,
        ],
    ],

    'service_manager' => [
        'factories' => [
            Service\AuthService::class => Service\Factory\AuthServiceFactory::class,
            // Configuration d'AuthenticationService avec storage en session
            AuthenticationService::class => function ($container) {
                $storage = new Session('UserAuth');
                return new AuthenticationService($storage);
            },
            Service/SumupService::class => Service\Factory\SumupServiceFactory::class,
        ],
        'aliases' => [
            'authentication' => AuthenticationService::class,
            'sumup_service' => Service\SumupService::class,
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

    'session_config' => [
        'cookie_lifetime' => 315360000, // 10 ans (en secondes)
        'gc_maxlifetime'  => 315360000, // 10 ans aussi
        'use_cookies'     => true,
        'use_only_cookies' => true,
        'cookie_httponly' => true,
    ],
    'session_manager' => [
        'validators' => [],
    ],
    'session_storage' => [
        'type' => \Laminas\Session\Storage\SessionArrayStorage::class,
    ],
];
