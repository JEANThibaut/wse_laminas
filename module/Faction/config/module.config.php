<?php
namespace Faction;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'faction' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/faction',
                    'defaults' => [
                        'controller' => Controller\FactionController::class,
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\FactionController::class => Controller\Factory\FactionControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\FactionManager::class => Service\Factory\FactionManagerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'template_map' => [
            // Layout spécifique pour le module Faction
            'layout/faction' => __DIR__ . '/../view/layout/faction.phtml',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'faction_entity' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Faction\Entity' => 'faction_entity',
                ],
            ],
        ],
    ],
];
