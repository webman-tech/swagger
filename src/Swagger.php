<?php

namespace WebmanTech\Swagger;

use Webman\Route;
use WebmanTech\Swagger\Controller\OpenapiController;
use WebmanTech\Swagger\Helper\ArrayHelper;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;

class Swagger
{
    public function registerGlobalRoute()
    {
        $config = array_merge(
            [
                'enable' => true,
                'openapi_doc' => [
                    'scan_path' => app_path(),
                ],
            ],
            config('plugin.webman-tech.swagger.app.global_route', [])
        );
        if (!$config['enable']) {
            return;
        }
        $this->registerRoute($config);
    }

    public function registerRoute(array $config = [])
    {
        $config = ArrayHelper::merge(
            [
                'route_prefix' => '/openapi',
                'host_forbidden' => [],
                'swagger_ui' => [],
                'openapi_doc' => [],
            ],
            $config
        );

        $hostForbiddenMiddleware = new HostForbiddenMiddleware($config['host_forbidden']);
        $controller = new OpenapiController();

        $docRoute = 'doc';

        Route::get($config['route_prefix'], function () use ($controller, $docRoute, $config) {
            return $controller->swaggerUI($docRoute, $config['swagger_ui']);
        })->middleware($hostForbiddenMiddleware);
        Route::get("{$config['route_prefix']}/{$docRoute}", function () use ($controller, $config) {
            return $controller->openapiDoc($config['openapi_doc']);
        })->middleware($hostForbiddenMiddleware);
    }
}