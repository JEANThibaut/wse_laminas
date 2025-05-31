<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return [

    'session_config' => [
        'cookie_lifetime' => 60 * 60 * 24 * 30, // 30 jours
        'gc_maxlifetime'  => 60 * 60 * 24 * 30,
    ],

    'session_manager' => [],
    'session_storage' => [],
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'params' => [
                    'dbname'   => 'wse',
                    'user'     => 'root',           // à adapter
                    'password' => '',               // à adapter
                    'host'     => '127.0.0.1',
                    'driver'   => 'pdo_mysql',
                    'charset'  => 'utf8mb4',
                ],
            ],
        ],
    ],

];


// $servername = "localhost";
// // $username = "api_wse";
// $username ="intr1140_api_wse"; 
// $password = "Api_WSE!27*";
// $dbname = "intr1140_wolf_soft_eure";

