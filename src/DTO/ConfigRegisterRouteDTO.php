<?php

namespace WebmanTech\Swagger\DTO;

/**
 * @property bool $enable 是否启用
 * @property string $route_prefix openapi 文档的路由前缀
 * @property ConfigHostForbiddenDTO $host_forbidden
 * @property ConfigSwaggerUiDTO $swagger_ui
 * @property ConfigOpenapiDocDTO $openapi_doc
 * @property bool $register_webman_route 是否注册 webman 的路由
 */
class ConfigRegisterRouteDTO extends BaseDTO
{
    public function initData()
    {
        $this->_data = array_merge([
            'enable' => true,
            'route_prefix' => '/openapi',
            'host_forbidden' => new ConfigHostForbiddenDTO(),
            'swagger_ui' => new ConfigSwaggerUiDTO(),
            'openapi_doc' => new ConfigOpenapiDocDTO(),
            'register_webman_route' => false,
        ], $this->_data);

        /* @phpstan-ignore-next-line */
        if (is_array($this->host_forbidden)) {
            $this->host_forbidden = new ConfigHostForbiddenDTO($this->host_forbidden);
        }
        /* @phpstan-ignore-next-line */
        if (is_array($this->swagger_ui)) {
            $this->swagger_ui = new ConfigSwaggerUiDTO($this->swagger_ui);
        }
        /* @phpstan-ignore-next-line */
        if (is_array($this->openapi_doc)) {
            $this->openapi_doc = new ConfigOpenapiDocDTO($this->openapi_doc);
        }
    }
}
