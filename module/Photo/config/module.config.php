<?php
namespace Photo;

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
        'photo-index' => [
            'type' => 'Literal',
            'options' => [
                'route'    => '/photo-index',
                'defaults' => [
                    'controller' => Controller\PhotoController::class,
                    'action'     => 'index',
                ],
            ],
        ],
    ],
],
    'controllers' => [
        'factories' => [
            Controller\PhotoController::class => Controller\Factory\PhotoControllerFactory::class, 

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

