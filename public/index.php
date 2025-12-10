<?php

declare(strict_types=1);

use Laminas\Mvc\Application;

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);



chdir(dirname(__DIR__));

// =================================================================
// Maintenance Mode Check - Version Finale et Fiable
// =================================================================
try {
    // --- 1. Connexion à la base de données ---
    $globalConfig = file_exists(__DIR__ . '/../config/autoload/global.php') ? require __DIR__ . '/../config/autoload/global.php' : [];
    $localConfig  = file_exists(__DIR__ . '/../config/autoload/local.php') ? require __DIR__ . '/../config/autoload/local.php' : [];
    
    $config = array_merge_recursive($globalConfig, $localConfig);
    $params = $config['doctrine']['connection']['orm_default']['params'];

    $dsn = "mysql:host={$params['host']};dbname={$params['dbname']};charset={$params['charset']}";
    $pdo = new PDO($dsn, $params['user'], $params['password']);

    // --- 2. Récupérer les réglages ---
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('maintenance_enabled', 'access_token')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // --- CORRECTION CRITIQUE ---
    // On vérifie explicitement si la valeur est '1'. Tout le reste désactive la maintenance.
    $maintenance_on = ($settings['maintenance_enabled'] ?? '0') === '1';
    $access_token   = $settings['access_token'] ?? 'secret';

} catch (Exception $e) {
    // LIGNE DE DÉBOGAGE : Affiche l'erreur exacte et arrête tout.
    die('Erreur de connexion a la base de donnees : ' . $e->getMessage());

    // Si la BDD ne répond pas, on active la maintenance par sécurité.
    $maintenance_on = true;
    $access_token   = 'error_token';
}

// --- 3. Logique de maintenance ---
if ($maintenance_on) {
    $access_granted = false;
    $token_from_url = $_GET['access_token'] ?? null;
    $token_from_cookie = $_COOKIE['site_access'] ?? null;

    if (($token_from_url === $access_token) || ($token_from_cookie === $access_token)) {
        $access_granted = true;
        if ($token_from_url === $access_token) {
            setcookie('site_access', $access_token, time() + 3600, '/');
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
    }

    if (! $access_granted) {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        readfile(__DIR__ . '/503.phtml');
        exit();
    }
}
// =================================================================
// Fin du bloc de maintenance
// =================================================================

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    if (is_string($path) && __FILE__ !== $path && is_file($path)) {
        return false;
    }
    unset($path);
}

// Composer autoloading
include __DIR__ . '/../vendor/autoload.php';

if (! class_exists(Application::class)) {
    throw new RuntimeException(
        "Unable to load application.\n"
        . "- Type `composer install` if you are developing locally.\n"
        . "- Type `docker-compose run laminas composer install` if you are using Docker.\n"
    );
}

$container = require __DIR__ . '/../config/container.php';
// Run the application!
/** @var Application $app */
$app = $container->get('Application');
$app->run();
