<?php

return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'driverClass' => Doctrine\DBAL\Driver\PDO\MySQL\Driver::class,
                // 'params' => [
                //     'host'     => 'localhost',
                //     'port'     => '3306',
                //     'user'     => 'intr1140_appli_flutter',
                //     'password' => 'Api_WSE!27*',
                //     'dbname'   => 'intr1140_wolf_soft_eure',
                //     'charset'  => 'utf8mb4',
                //     'driverOptions' => [
                //         \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                //     ],
                // ],

                // 'params' => [
                //     'host'     => 'localhost',
                //     'port'     => '3306',
                //     'user'     => 'intr1140_api_wse',
                //     'password' => '69uiFxjx2z8SvEZ',
                //     'dbname'   => 'intr1140_wolf_soft_eure',
                //     'charset'  => 'utf8mb4',
                //     'driverOptions' => [
                //         \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                //     ],
                // ],
              
                'params' => [
                    'host'     => 'localhost',
                    'port'     => '3306',
                    'user'     => 'root',
                    'password' => '',
                    'dbname'   => 'wse_api',
                    'charset'  => 'utf8mb4',
                    'driverOptions' => [
                        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                    ],
                ],
            ],
        ],
        'configuration' => [
            'orm_default' => [
                'metadata_cache'    => 'array',
                'query_cache'       => 'array',
                'result_cache'      => 'array',
                'hydration_cache'   => 'array',
                'driver'            => 'orm_default',
                'generate_proxies'  => true,
                'proxy_dir'         => 'data/DoctrineORMModule/Proxy',
                'proxy_namespace'   => 'DoctrineORMModule\Proxy',
            ],
        ],
            'driver' => [
                'users_driver' => [
                    'class' => Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                    'paths' => [
                        __DIR__ . '/../../module/User/src/Entity', // Ne doit contenir que les entités !
                    ],
                ],
                'orm_default' => [
                    'drivers' => [
                        'User\Entity' => 'users_driver', // On s'assure que seul Users\Entity est mappé
                    ],
                ],
            ],
    ],
];
