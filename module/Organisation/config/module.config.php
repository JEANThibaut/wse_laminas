<?php
namespace Organisation;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

/**
 * Routes simples : [nom, type, adresse, action].
 */
$routes = [
    ['organisation', Literal::class, '/organisation', 'index'],
    ['organisation-calendar', Literal::class, '/organisation/calendrier', 'calendar'],
    ['organisation-view', Segment::class, '/organisation/voir/:id', 'view'],
    ['organisation-new', Segment::class, '/organisation/nouveau/:type', 'new'],
    ['organisation-edit', Segment::class, '/organisation/modifier/:id', 'edit'],
    ['organisation-delete', Literal::class, '/organisation/supprimer', 'delete'],
    ['organisation-pin', Literal::class, '/organisation/epingler', 'pin'],
    ['organisation-vote', Literal::class, '/organisation/voter', 'vote'],
    ['organisation-answer', Literal::class, '/organisation/repondre', 'answer'],
];

$router = [];
foreach ($routes as [$name, $type, $route, $action]) {
    $options = ['route' => $route, 'defaults' => ['controller' => Controller\OrganisationController::class, 'action' => $action]];
    if ($type === Segment::class) {
        $options['constraints'] = ['id' => '[0-9]+', 'type' => '[a-z]+'];
    }
    $router[$name] = ['type' => $type, 'options' => $options];
}

return [
    'router' => [
        'routes' => $router,
    ],
    'controllers' => [
        'factories' => [
            Controller\OrganisationController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\OrganisationAccess::class => ReflectionBasedAbstractFactory::class,
            Service\OrganisationManager::class => ReflectionBasedAbstractFactory::class,
            Service\OrganisationNotifier::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_helpers' => [
        'aliases' => [
            'organisationAccess' => View\Helper\OrganisationAccess::class,
            'organisationPinned' => View\Helper\OrganisationPinned::class,
        ],
        'factories' => [
            View\Helper\OrganisationAccess::class => ReflectionBasedAbstractFactory::class,
            View\Helper\OrganisationPinned::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'Organisation_entity' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Organisation\Entity' => 'Organisation_entity',
                ],
            ],
        ],
    ],
];
