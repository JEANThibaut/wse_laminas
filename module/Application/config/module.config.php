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
            'push-subscribe' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/push/subscribe',
                    'defaults' => [
                        'controller' => Controller\PushController::class,
                        'action'     => 'subscribe',
                    ],
                ],
            ],
            'push-unsubscribe' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/push/unsubscribe',
                    'defaults' => [
                        'controller' => Controller\PushController::class,
                        'action'     => 'unsubscribe',
                    ],
                ],
            ],
            'push-preferences' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/push/preferences',
                    'defaults' => [
                        'controller' => Controller\PushController::class,
                        'action'     => 'preferences',
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
            'send-validation-email' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/send-validation-email',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'sendValidationEmail',
                    ],
                ],
            ],
            'validate-email' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/validate-email',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'validateEmail',
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
            Controller\PushController::class => Controller\Factory\PushControllerFactory::class,

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
            Service\SumUpService::class => Service\Factory\SumUpServiceFactory::class,
            Service\PwaAccessPolicy::class => Service\Factory\PwaAccessPolicyFactory::class,
            Service\PushService::class => Service\Factory\PushServiceFactory::class,
            Service\FacebookPublisher::class => Service\Factory\FacebookPublisherFactory::class,
        ],
        'aliases' => [
            'authentication' => AuthenticationService::class,
            'sumup_service' => Service\SumUpService::class,
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

    'view_helpers' => [
        'aliases' => [
            'mailCheck' => View\Helper\MailCheck::class,
            'pwaAccess' => View\Helper\PwaAccess::class,
            'pushPublicKey' => View\Helper\PushPublicKey::class,
        ],
        'factories' => [
            View\Helper\MailCheck::class => InvokableFactory::class,
            View\Helper\PwaAccess::class => View\Helper\Factory\PwaAccessFactory::class,
            View\Helper\PushPublicKey::class => View\Helper\Factory\PushPublicKeyFactory::class,
        ],
    ],

    // Application installable (PWA) et notifications. En phase de test :
    // manifeste, service worker, bouton "Installer l'application",
    // notifications du profil, abonnements et envois sont reserves aux comptes
    // GOD. Passer restricted a false pour l'ouvrir a tous.
    // Les cles VAPID ('push' => ['vapid' => ...]) sont generees au deploiement
    // depuis les secrets GitHub (config/autoload/push.global.php), jamais dans le repo.
    'pwa' => [
        'restricted' => true,
    ],

    // Publication sur la Page Facebook, depuis l'onglet GOD MODE > Publication.
    // page_id et token : config/autoload/facebook.global.php,
    // genere au deploiement depuis les secrets GitHub FACEBOOK_PAGE_ID et
    // FACEBOOK_PAGE_TOKEN. live = false : publications NON publiees (visibles
    // des seuls admins de la Page), le temps des tests.
    'facebook' => [
        'live' => false,
        'graph_version' => 'v21.0',
        // Marqueurs : {jour}, {date}, {places}, {lien}
        'template' => "🎯 Nouvelle partie le {jour} {date} !\n\n{places} places disponibles, les inscriptions sont ouvertes : {lien}",
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
