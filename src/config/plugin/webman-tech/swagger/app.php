<?php
return [
    'enable' => true,
    'global_route' => [
        /**
         * 全局扫描的配置
         * @see \WebmanTech\Swagger\Swagger::registerGlobalRoute()
         */
        'enable' => true,
    ],
    'host_forbidden' => [
        /**
         * 全局的 host forbidden 配置
         * @see \WebmanTech\Swagger\Middleware\HostForbiddenMiddleware::$config
         */
        'enable' => true,
        'host_white_list' => [],
    ],
    'swagger_ui' => [
        /**
         * 全局的 swagger ui 配置
         * @see \WebmanTech\Swagger\Controller\OpenapiController::swaggerUI()
         */
    ],
    'openapi_doc' => [
        /**
         * 是否自动注册 webman 的路由
         */
        'register_webman_route' => false,
        /**
         * 全局的 openapi doc 配置
         * @see \WebmanTech\Swagger\Controller\OpenapiController::openapiDoc()
         */
    ],
];