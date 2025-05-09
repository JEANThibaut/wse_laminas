<?php

declare(strict_types=1);

namespace Forum;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'get-all-topics' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/forum/get-all-topics',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'getAllTopics',
                    ],
                ],
            ],
            'get-topic' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/forum/get-topic[/:id]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'getTopic',
                    ],
                ],
            ],              
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Controller\Factory\IndexControllerFactory::class,
        ],
    ],
    'view_manager' => [
        // Activer les détails d'erreurs et d'exceptions uniquement en développement
        'display_not_found_reason' => true,
        'display_exceptions'       => true,

        // Type de document HTML
        'doctype'                  => 'HTML5',

        // Chemin pour les templates de vue spécifiques à ce module
        'template_path_stack' => [
            __DIR__ . '/../view', // Chemin vers les vues de ce module
        ],

        // Gestion des réponses JSON
        'strategies' => [
            'ViewJsonStrategy', // Active la stratégie pour les réponses JSON
        ],
    ],
];
