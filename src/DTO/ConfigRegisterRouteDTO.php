<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;

final class ConfigRegisterRouteDTO extends BaseConfigDTO
{
    public function __construct(
        public bool                    $enable = true, // 是否启用
        public string                  $route_prefix = '/openapi', // openapi 文档的路由前缀
        public ?ConfigHostForbiddenDTO $host_forbidden = null, // 允许访问的 host
        public ?ConfigSwaggerUiDTO     $swagger_ui = null, // swagger ui 的配置
        public ?ConfigOpenapiDocDTO    $openapi_doc = null, // openapi 文档的配置
        public bool                    $register_webman_route = false, // 是否注册 webman 的路由
    )
    {
    }

    protected static function getAppConfig(): array
    {
        return [
            'host_forbidden' => ConfigHelper::get('app.host_forbidden', []),
            'swagger_ui' => ConfigHelper::get('app.swagger_ui', []),
            'openapi_doc' => ConfigHelper::get('app.openapi_doc', []),
        ];
    }
}
