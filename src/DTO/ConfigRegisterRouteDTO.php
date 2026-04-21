<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\Swagger\Middleware\BasicAuthMiddleware;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;

final class ConfigRegisterRouteDTO extends BaseConfigDTO
{
    public ConfigHostForbiddenDTO $host_forbidden;
    public ConfigBasicAuthDTO $basic_auth;
    public ConfigSwaggerUiDTO $swagger_ui;
    public ConfigOpenapiDocDTO $openapi_doc;
    public bool $register_route;

    /**
     * @var array<int, object|string> 路由中间件列表
     */
    public array $middlewares;

    public function __construct(
        public bool                       $enable = true, // 是否启用
        public string                     $route_prefix = '/openapi', // openapi 文档的路由前缀
        null|array|ConfigHostForbiddenDTO $host_forbidden = null, // 允许访问的 host
        null|array|ConfigBasicAuthDTO     $basic_auth = null, // Basic 认证配置
        null|array|ConfigSwaggerUiDTO     $swagger_ui = null, // swagger ui 的配置
        null|array|ConfigOpenapiDocDTO    $openapi_doc = null, // openapi 文档的配置
        bool|null                         $register_webman_route = null, // 是否注册 webman 的路由（弃用，请使用 register_route）
        bool|null                         $register_route = null, // 是否注册路由
        array                             $middlewares = [], // 额外的中间件（如 auth 认证中间件）
    )
    {
        $this->host_forbidden = ConfigHostForbiddenDTO::fromConfig($host_forbidden ?? []);
        $this->basic_auth = ConfigBasicAuthDTO::fromConfig($basic_auth ?? []);
        $this->swagger_ui = ConfigSwaggerUiDTO::fromConfig($swagger_ui ?? []);
        $this->openapi_doc = ConfigOpenapiDocDTO::fromConfig($openapi_doc ?? []);
        $this->register_route = $register_route ?? $register_webman_route ?? false;
        $this->middlewares = array_merge(
            [
                new HostForbiddenMiddleware($this->host_forbidden),
                new BasicAuthMiddleware($this->basic_auth),
            ],
            $middlewares,
        );
    }
}
