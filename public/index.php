<?php

declare(strict_types=1);

use Laminas\Mvc\Application;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SessionManager;
use Laminas\Session\Container;

// Ajouter les en-têtes CORS pour permettre les requêtes entre le frontend et le backend
header("Access-Control-Allow-Origin: http://localhost:3000");  // Change si ton frontend a une autre URL
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Répondre correctement aux requêtes préliminaires `OPTIONS`
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Définir le répertoire de travail à la racine du projet
chdir(dirname(__DIR__));

// Décliner les requêtes vers des fichiers statiques pour le serveur PHP intégré
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_string($path) && __FILE__ !== $path && is_file($path)) {
        return false;
    }
    unset($path);
}

// Inclure l'autoload de Composer
include __DIR__ . '/../vendor/autoload.php';

// Initialisation de la session (à ajouter ici)
$sessionConfig = new SessionConfig();
$sessionConfig->setOptions([
    'cookie_lifetime' => 3600,           // Durée de vie du cookie de session (1 heure)
    'gc_maxlifetime'  => 3600,           // Durée de vie de la session côté serveur
    'cookie_samesite' => 'None',          // Autoriser les requêtes cross-origin
    'cookie_secure'   => true,           // `false` en HTTP, `true` en HTTPS
]);

$sessionManager = new SessionManager($sessionConfig);
Container::setDefaultManager($sessionManager);
$sessionManager->start();

// Vérifier si la classe Application existe
if (! class_exists(Application::class)) {
    throw new RuntimeException(
        "Unable to load application.\n"
        . "- Type `composer install` if you are developing locally.\n"
        . "- Type `docker-compose run laminas composer install` if you are using Docker.\n"
    );
}

// Charger le conteneur de dépendances
$container = require __DIR__ . '/../config/container.php';

// Exécuter l'application Laminas
$container->get('Application')->run();
