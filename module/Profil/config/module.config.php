<?php
namespace Profil;

use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Profil\Controller\ProfilController;
use Profil\Controller\AjaxController;
use Profil\Service\RepliqueManager;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;

return [
    'router' => [
        'routes' => [
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
            'ajax-get-repliques' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/ajax-get-repliques',
                    'defaults' => [
                        'controller' => AjaxController::class,
                        'action' => 'ajaxGetRepliques',
                    ],
                ],
            ],
            'ajax-add-replique' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/ajax-add-replique',
                    'defaults' => [
                        'controller' => AjaxController::class,
                        'action' => 'ajaxAddReplique',
                    ],
                ],
            ],
            'ajax-delete-replique' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/ajax-delete-replique',
                    'defaults' => [
                        'controller' => AjaxController::class,
                        'action' => 'ajaxDeleteReplique',
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

