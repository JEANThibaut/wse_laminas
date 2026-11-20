<?php
namespace Messagerie;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

/**
 * Routes simples : [nom, type, adresse, controleur, action].
 */
$routes = [
    ['messagerie', Literal::class, '/messagerie', Controller\MessagerieController::class, 'inbox'],
    ['messagerie-conversation', Segment::class, '/messagerie/conversation/:id', Controller\MessagerieController::class, 'conversation'],
    ['messagerie-new', Segment::class, '/messagerie/contacter/:annonce', Controller\MessagerieController::class, 'new'],
    ['messagerie-block', Literal::class, '/messagerie/bloquer', Controller\MessagerieController::class, 'block'],
    ['messagerie-archive', Literal::class, '/messagerie/archiver', Controller\MessagerieController::class, 'archive'],
    // GOD MODE > Messagerie
    ['admin-messagerie', Literal::class, '/admin/messagerie', Controller\MessagerieAdminController::class, 'send'],
];

$router = [];
foreach ($routes as [$name, $type, $route, $controller, $action]) {
    $options = ['route' => $route, 'defaults' => ['controller' => $controller, 'action' => $action]];
    if ($type === Segment::class) {
        $options['constraints'] = ['id' => '[0-9]+', 'annonce' => '[0-9]+'];
    }
    $router[$name] = ['type' => $type, 'options' => $options];
}

return [
    'router' => [
        'routes' => $router,
    ],
    'controllers' => [
        'factories' => [
            Controller\MessagerieController::class => ReflectionBasedAbstractFactory::class,
            Controller\MessagerieAdminController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\MessagerieAccess::class => ReflectionBasedAbstractFactory::class,
            Service\MessagerieManager::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_helpers' => [
        'aliases' => [
            'unreadMessages' => View\Helper\UnreadMessages::class,
        ],
        'factories' => [
            View\Helper\UnreadMessages::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'Messagerie_entity' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Messagerie\Entity' => 'Messagerie_entity',
                ],
            ],
        ],
    ],
];
