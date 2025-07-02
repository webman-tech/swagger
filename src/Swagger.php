<?php

namespace WebmanTech\Swagger;

use Webman\Route;
use WebmanTech\Swagger\Controller\OpenapiController;
use WebmanTech\Swagger\DTO\ConfigRegisterRouteDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;
use WebmanTech\Swagger\RouteAnnotation\Reader;
use WebmanTech\Swagger\RouteAnnotation\Register;

final class Swagger
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * 注册全局
     */
    public function registerGlobalRoute(): void
    {
        $config = ConfigRegisterRouteDTO::fromConfig(ConfigHelper::get('app.global_route', []));
        if (!$config->openapi_doc->scan_path) {
            $config->openapi_doc->scan_path = [app_path()];
        }
        $this->registerRoute($config);
    }

    /**
     * 根据配置注册
     */
    public function registerRoute(array|ConfigRegisterRouteDTO $config): void
    {
        $config = ConfigRegisterRouteDTO::fromConfig($config);
        if (!$config->enable) {
            return;
        }

        if (!$config->openapi_doc->scan_path) {
            throw new \InvalidArgumentException('openapi_doc.scan_path is required');
        }

        $hostForbiddenMiddleware = new HostForbiddenMiddleware($config->host_forbidden);
        $controller = new OpenapiController();

        $swaggerRoute = $config->route_prefix;
        $docUrl = 'doc';
        $docRoute = rtrim($swaggerRoute, '/') . '/' . $docUrl;

        // 注册 swagger 访问的路由
        Route::get($swaggerRoute, fn() => $controller->swaggerUI($docUrl, $config->swagger_ui))->middleware($hostForbiddenMiddleware);
        // 注册 openapi doc 的路由
        Route::get($docRoute, fn() => $controller->openapiDoc($config->openapi_doc))->middleware($hostForbiddenMiddleware);

        // 注册 api 接口路由
        if ($config->register_webman_route) {
            $reader = new Reader();
            $register = new Register($reader->getData($config->openapi_doc->scan_path));
            $register->registerRoute();
        }
    }
}
