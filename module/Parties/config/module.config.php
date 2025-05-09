<?php

declare(strict_types=1);

namespace Parties;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'get-all-parties' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/parties/get-all-parties',
                    'defaults' => [
                        'controller' => Controller\PartieController::class,
                        'action'     => 'index',
                    ],
                ],
            ],    
            'get-partie' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/parties/get-partie[/:id]',
                    'defaults' => [
                        'controller' => Controller\PartieController::class,
                        'action'     => 'getPartie',
                    ],
                ],
            ],         
            'get-next-partie' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/parties/get-next-partie',
                    'defaults' => [
                        'controller' => Controller\PartieController::class,
                        'action'     => 'getNextPartie',
                    ],
                ],
            ],       
            'add-partie' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/parties/add-partie',
                    'defaults' => [
                        'controller' => Controller\PartieController::class,
                        'action'     => 'addPartie',
                    ],
                ],
            ],    
            'delete-partie' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/parties/delete-partie[/:id]',
                    'defaults' => [
                        'controller' => Controller\PartieController::class,
                        'action'     => 'deletePartie',
                    ],
                ],
            ],    
            'register-player-partie' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/participants/register-player-partie',
                    'defaults' => [
                        'controller' => Controller\ParticipantController::class,
                        'action'     => 'registerPlayerPartie',
                    ],
                ],
            ],    
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Controller\Factory\IndexControllerFactory::class,
            Controller\PartieController::class => Controller\Factory\PartieControllerFactory::class,
            Controller\ParticipantController::class => Controller\Factory\ParticipantControllerFactory::class,
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
