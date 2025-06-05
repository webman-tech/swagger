<?php

namespace WebmanTech\Swagger;

use Webman\Route;
use WebmanTech\Swagger\Controller\OpenapiController;
use WebmanTech\Swagger\DTO\ConfigRegisterRouteDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;
use WebmanTech\Swagger\RouteAnnotation\Reader;
use WebmanTech\Swagger\RouteAnnotation\Register;

class Swagger
{
    /**
     * 注册全局
     * @return void
     */
    public function registerGlobalRoute()
    {
        $config = new ConfigRegisterRouteDTO((array)ConfigHelper::get('app.global_route', []));
        if (!$config->openapi_doc->scan_path) {
            $config->openapi_doc->scan_path = [app_path()];
        }
        $this->registerRoute($config);
    }

    /**
     * 根据配置注册
     * @param array|ConfigRegisterRouteDTO $config
     * @return void
     */
    public function registerRoute($config)
    {
        if (!$config instanceof ConfigRegisterRouteDTO) {
            $config = new ConfigRegisterRouteDTO($config);
        }
        if (!$config->enable) {
            return;
        }

        if (!$config->openapi_doc->scan_path) {
            throw new \InvalidArgumentException('openapi_doc.scan_path is required');
        }

        $hostForbiddenMiddleware = new HostForbiddenMiddleware($config->host_forbidden);
        $controller = new OpenapiController();

        $docRoute = 'doc';

        // 注册 swagger 访问的路由
        Route::get($config->route_prefix, function () use ($controller, $docRoute, $config) {
            return $controller->swaggerUI($docRoute, $config->swagger_ui);
        })->middleware($hostForbiddenMiddleware);
        // 注册 openapi doc 的路由
        Route::get("{$config->route_prefix}/{$docRoute}", function () use ($controller, $config) {
            return $controller->openapiDoc($config->openapi_doc);
        })->middleware($hostForbiddenMiddleware);

        // 注册 api 接口路由
        if ($config->register_webman_route) {
            $reader = new Reader();
            $register = new Register($reader->getData($config->openapi_doc->scan_path));
            $register->registerRoute();
        }
    }
}
