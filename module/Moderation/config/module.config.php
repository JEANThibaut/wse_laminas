<?php
namespace Moderation;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

/**
 * Routes simples : [nom, type, adresse, controleur, action].
 */
$routes = [
    ['report', Literal::class, '/signaler', Controller\ReportController::class, 'report'],
    // GOD MODE > Signalements
    ['admin-moderation', Literal::class, '/admin/signalements', Controller\ModerationAdminController::class, 'queue'],
    ['admin-moderation-decide', Literal::class, '/admin/signalements/decision', Controller\ModerationAdminController::class, 'decide'],
    ['admin-moderation-approve', Literal::class, '/admin/signalements/valider', Controller\ModerationAdminController::class, 'approve'],
    ['admin-moderation-sanctions',Literal::class, '/admin/signalements/sanctions', Controller\ModerationAdminController::class, 'sanctions'],
    ['admin-moderation-revoke', Literal::class, '/admin/signalements/sanctions/lever', Controller\ModerationAdminController::class, 'revoke'],
    ['admin-moderation-conversation', Segment::class, '/admin/signalements/conversation/:id', Controller\ModerationAdminController::class, 'conversation'],
];

$router = [];
foreach ($routes as [$name, $type, $route, $controller, $action]) {
    $options = ['route' => $route, 'defaults' => ['controller' => $controller, 'action' => $action]];
    if ($type === Segment::class) {
        $options['constraints'] = ['id' => '[0-9]+'];
    }
    $router[$name] = ['type' => $type, 'options' => $options];
}

return [
    'router' => [
        'routes' => $router,
    ],
    'controllers' => [
        'factories' => [
            Controller\ReportController::class => ReflectionBasedAbstractFactory::class,
            Controller\ModerationAdminController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\ReportService::class => ReflectionBasedAbstractFactory::class,
            Service\SanctionService::class => ReflectionBasedAbstractFactory::class,
            Service\ModerationManager::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_helpers' => [
        'aliases' => [
            'moderationTodo' => View\Helper\ModerationTodo::class,
        ],
        'factories' => [
            View\Helper\ModerationTodo::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'Moderation_entity' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Moderation\Entity' => 'Moderation_entity',
                ],
            ],
        ],
    ],
];
