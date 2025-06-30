<?php
namespace Annonce;

use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Annonce\Controller\AnnonceController;
use Annonce\Controller\AjaxController;
use Annonce\Service\AnnonceManager;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;

return [
    'router' => [
        'routes' => [
            'annonces' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/annonces',
                    'defaults' => [
                        'controller' => AnnonceController::class,
                        'action' => 'annonces',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\AnnonceController::class => Controller\Factory\AnnonceControllerFactory::class, 

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
            Service\AnnonceManager::class => Service\Factory\AnnonceManagerFactory::class,
        ],
    ],
    // 'template_path_stack' => [
    //     'Annonce' => __DIR__ . '/../view',
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
                'Annonce\Entity' => 'Profil_entity',
            ],
        ],
    ],
],
];

