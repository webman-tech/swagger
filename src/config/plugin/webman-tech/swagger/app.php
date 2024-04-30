<?php
return [
    /**
     * 全局开关
     */
    'enable' => true,
    /**
     * 全局扫描的配置
     * @see \WebmanTech\Swagger\Swagger::registerGlobalRoute()
     * @see \WebmanTech\Swagger\DTO\ConfigRegisterRouteDTO
     */
    'global_route' => [
        'enable' => true,
        'register_webman_route' => false,
    ],
    /**
     * 全局的 host forbidden 配置
     * @see \WebmanTech\Swagger\DTO\ConfigHostForbiddenDTO
     */
    'host_forbidden' => [
        'enable' => true,
        'host_white_list' => [],
    ],
    /**
     * 全局的 swagger ui 配置
     * @see \WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO
     */
    'swagger_ui' => [
    ],
    /**
     * 全局的 openapi doc 配置
     * @see \WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO
     */
    'openapi_doc' => [

    ],
];