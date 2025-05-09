<?php

declare(strict_types=1);

namespace Parties;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'parties' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/parties',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'transform-email-to-id-user' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/parties/transform-email-to-id-user',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'transformEmailToIdUser',
                    ],
                ],
            ],
            'transform-partie-id-for-real' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/parties/transform-partie-id-for-real',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'transformPartieIdForReal',
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
    // 'view_manager' => [
    //     'display_not_found_reason' => true,
    //     'display_exceptions'       => true,
    //     'doctype'                  => 'HTML5',
    //     'template_path_stack' => [
    //         __DIR__ . '/../view',
    //     ],
    // ],
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
