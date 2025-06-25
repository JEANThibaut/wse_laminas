<?php
namespace Profil;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Profil\Controller\ProfilController;
use Profil\Controller\AjaxController;
use Profil\Service\RepliqueManager;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;

return [
    'router' => [
        'routes' => [
            'profil-index' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/profil-index',
                    'defaults' => [
                        'controller' => ProfilController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'profil' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/profil',
                    'defaults' => [
                        'controller' => ProfilController::class,
                        'action' => 'profil',
                    ],
                ],
            ],
            'profil-edit' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/profil-edit[/:id]',
                    'defaults' => [
                        'controller' => ProfilController::class,
                        'action' => 'profilEdit',
                    ],
                ],
            ],
            'arsenal' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/arsenal',
                    'defaults' => [
                        'controller' => ProfilController::class,
                        'action' => 'arsenal',
                    ],
                ],
            ],
            'update-replique' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/update-replique',
                    'defaults' => [
                        'controller' => ProfilController::class,
                        'action' => 'updateReplique',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ProfilController::class => Controller\Factory\ProfilControllerFactory::class, 
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
            Service\RepliqueManager::class => Service\Factory\RepliqueManagerFactory::class,
        ],
    ],
    // 'template_path_stack' => [
    //     'profil' => __DIR__ . '/../view',
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
                'Profil\Entity' => 'Profil_entity',
            ],
        ],
    ],
],
];

