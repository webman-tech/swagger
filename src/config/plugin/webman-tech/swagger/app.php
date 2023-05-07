<?php
return [
    'enable' => true,
    'global_route' => [
        /**
         * @see \WebmanTech\Swagger\Swagger::registerGlobalRoute()
         */
        'enable' => true,
        'scan_paths' => app_path(),
    ],
    'host_forbidden' => [
        /**
         * @see \WebmanTech\Swagger\Middleware\HostForbiddenMiddleware::$config
         */
        'enable' => true,
        'host_white_list' => [],
    ]
];