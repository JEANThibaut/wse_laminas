<?php
return [
    'session_config' => [
        'cookie_lifetime' => 3600,           // Durée de vie du cookie (1 heure)
        'gc_maxlifetime'  => 3600,           // Durée de vie de la session côté serveur
        'cookie_samesite' => 'None',          // Autorise les requêtes cross-origin
        'cookie_secure'   => true,           // Mettre `true` si en HTTPS, `false` sinon
        'use_cookies'     => true,            // Active l'utilisation de cookies
    ],
    'session_storage' => [
        'type' => \Laminas\Session\Storage\SessionArrayStorage::class,
    ],
    'session_manager' => [
        'validators' => [
            \Laminas\Session\Validator\RemoteAddr::class,
            \Laminas\Session\Validator\HttpUserAgent::class,
        ],
    ],
];
