<?php

/**
 * If you need an environment-specific system or application configuration,
 * there is an example in the documentation
 *
 * @see https://docs.laminas.dev/tutorials/advanced-config/#environment-specific-system-configuration
 * @see https://docs.laminas.dev/tutorials/advanced-config/#environment-specific-application-configuration
 */

return [
    'modules' => require __DIR__ . '/modules.config.php',
    'module_listener_options' => [
        'use_laminas_loader' => false,
        'config_glob_paths' => [
            realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php',
        ],
        'config_cache_enabled' => true,
        'config_cache_key' => 'application.config.cache',
        'module_map_cache_enabled' => true,
        'module_map_cache_key' => 'application.module.cache',
        'cache_dir' => 'data/cache/',

    ],


    'service_manager' => [
        'factories' => [
            Application\Middleware\CorsMiddleware::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
        ],
    ],
    'middleware_pipeline' => [
        'always' => [
            'middleware' => [
                Application\Middleware\CorsMiddleware::class, 
            ],
            'priority' => 10000, 
        ],
    ],
];
