<?php
namespace Forum;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Forum\Controller\ForumController;
use Forum\Controller\AjaxController;
use Forum\Service\ForumManager;
use Application\Service\AuthService;
use Application\Service\Factory\AuthServiceFactory;

return [
    'router' => [
        'routes' => [
            'forum-index' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/forum-index',
                    'defaults' => [
                        'controller' => ForumController::class,
                        'action' => 'forumIndex',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ForumController::class => Controller\Factory\ForumControllerFactory::class, 

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
            Service\ForumManager::class => Service\Factory\ForumManagerFactory::class,
        ],
    ],
    // 'template_path_stack' => [
    //     'Forum' => __DIR__ . '/../view',
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
                'Forum\Entity' => 'Profil_entity',
            ],
        ],
    ],
],
];

