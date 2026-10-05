<?php
namespace Game;

use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Game\Controller\GameController;
use Game\Controller\AjaxController;
use Game\Service\GameManager;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;

return [
    'router' => [
        'routes' => [
            'register-in-game' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/register-in-game',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'registerInGame',
                    ],
                ],
            ],
            'register-in-game-payment-return' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/register-in-game/payment-return',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'registerInGamePaymentReturn',
                    ],
                ],
            ],
            'sumup-webhook' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/webhooks/sumup',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'sumupWebhook',
                    ],
                ],
            ],
            // File d'attente (Game\Service\QueueManager), en POST
            'queue-join' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/queue/join',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'queueJoin',
                    ],
                ],
            ],
            'queue-leave' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/queue/leave',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'queueLeave',
                    ],
                ],
            ],
            'queue-accept' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/queue/accept',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'queueAccept',
                    ],
                ],
            ],
            'queue-decline' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/queue/decline',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'queueDecline',
                    ],
                ],
            ],
            'unregister-in-game' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/unregister-in-game',
                    'defaults' => [
                        'controller' => GameController::class,
                        'action' => 'unregisterInGame',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\GameController::class => Controller\Factory\GameControllerFactory::class, 
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
            Service\GameManager::class => Service\Factory\GameManagerFactory::class,
            Service\QueueManager::class => Service\Factory\QueueManagerFactory::class,
        ],
    ],
    // File d'attente : en phase de test, visible et utilisable par les admins
    // seulement (GOD compris). Passer restricted a false pour l'ouvrir a tous.
    // auto_offer : false = un admin propose lui-meme les places liberees
    // (fiche de la partie) ; true = chaque place liberee est proposee
    // automatiquement au premier de la file.
    'queue' => [
        'restricted' => true,
        'auto_offer' => false,
        'site_url' => 'https://www.wolfsofteure.fr',
    ],
    // 'template_path_stack' => [
    //     'Game' => __DIR__ . '/../view',
    // ],
    'doctrine' => [
        'driver' => [
            'Game_entity' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Game\Entity' => 'Game_entity',
                ],
            ],
        ],
    ],
];

