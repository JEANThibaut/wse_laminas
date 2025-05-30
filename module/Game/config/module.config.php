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

