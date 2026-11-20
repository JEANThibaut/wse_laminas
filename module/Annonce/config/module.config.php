<?php
namespace Annonce;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

/**
 * Routes simples : [nom, type, adresse, controleur, action].
 */
$routes = [
    // Espace membres
    ['annonces', Literal::class, '/annonces', Controller\AnnonceController::class, 'index'],
    ['annonces-view', Segment::class, '/annonces/voir/:id', Controller\AnnonceController::class, 'view'],
    ['annonces-mine', Literal::class, '/annonces/mes-annonces', Controller\AnnonceController::class, 'mine'],
    ['annonces-new', Literal::class, '/annonces/nouvelle', Controller\AnnonceController::class, 'new'],
    ['annonces-edit', Segment::class, '/annonces/modifier/:id', Controller\AnnonceController::class, 'edit'],
    ['annonces-renew', Literal::class, '/annonces/renouveler', Controller\AnnonceController::class, 'renew'],
    ['annonces-withdraw', Literal::class, '/annonces/retirer', Controller\AnnonceController::class, 'withdraw'],
    ['annonces-photo', Segment::class, '/annonces/photo/:id', Controller\AnnonceController::class, 'photo'],
    ['annonces-cgu', Literal::class, '/annonces/conditions', Controller\AnnonceController::class, 'cgu'],
    // GOD MODE > Petites annonces
    ['admin-annonces', Literal::class, '/admin/annonces', Controller\AnnonceAdminController::class, 'settings'],
    ['admin-annonces-tags', Literal::class, '/admin/annonces/categories', Controller\AnnonceAdminController::class, 'tags'],
    ['admin-annonces-tag-edit', Literal::class, '/admin/annonces/categories/modifier', Controller\AnnonceAdminController::class, 'tagEdit'],
    ['admin-annonces-tag-move', Literal::class, '/admin/annonces/categories/deplacer', Controller\AnnonceAdminController::class, 'tagMove'],
    ['admin-annonces-tag-delete', Literal::class, '/admin/annonces/categories/supprimer', Controller\AnnonceAdminController::class, 'tagDelete'],
    ['admin-annonces-cgu', Literal::class, '/admin/annonces/conditions', Controller\AnnonceAdminController::class, 'cgu'],
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
            Controller\AnnonceController::class => ReflectionBasedAbstractFactory::class,
            Controller\AnnonceAdminController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Service\AnnonceSettings::class => ReflectionBasedAbstractFactory::class,
            Service\AnnonceAccess::class => ReflectionBasedAbstractFactory::class,
            Service\AnnonceManager::class => ReflectionBasedAbstractFactory::class,
            Service\CguService::class => ReflectionBasedAbstractFactory::class,
            Service\PhotoStorage::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'doctrine' => [
        'driver' => [
            'Annonce_entity' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Annonce\Entity' => 'Annonce_entity',
                ],
            ],
        ],
    ],
];
