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
