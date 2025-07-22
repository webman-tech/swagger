<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\DTO\BaseConfigDTO;

final class ConfigRegisterRouteDTO extends BaseConfigDTO
{
    public ConfigHostForbiddenDTO $host_forbidden;
    public ConfigSwaggerUiDTO $swagger_ui;
    public ConfigOpenapiDocDTO $openapi_doc;
    public bool $register_route;

    public function __construct(
        public bool                       $enable = true, // 是否启用
        public string                     $route_prefix = '/openapi', // openapi 文档的路由前缀
        null|array|ConfigHostForbiddenDTO $host_forbidden = null, // 允许访问的 host
        null|array|ConfigSwaggerUiDTO     $swagger_ui = null, // swagger ui 的配置
        null|array|ConfigOpenapiDocDTO    $openapi_doc = null, // openapi 文档的配置
        bool|null                         $register_webman_route = null, // 是否注册 webman 的路由（弃用，请使用 register_route）
        bool|null                         $register_route = null, // 是否注册路由
    )
    {
        $this->host_forbidden = ConfigHostForbiddenDTO::fromConfig($host_forbidden ?? []);
        $this->swagger_ui = ConfigSwaggerUiDTO::fromConfig($swagger_ui ?? []);
        $this->openapi_doc = ConfigOpenapiDocDTO::fromConfig($openapi_doc ?? []);
        $this->register_route = $register_route ?? $register_webman_route ?? false;
    }
}
