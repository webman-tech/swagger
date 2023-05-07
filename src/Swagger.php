<?php

namespace WebmanTech\Swagger;

use Webman\Route;
use WebmanTech\Swagger\Controller\OpenapiController;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;

class Swagger
{
    public function registerGlobalRoute()
    {
        $config = array_merge(
            [
                'enable' => true,
                'scan_paths' => [app_path()],
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
        $config = array_merge(
            [
                'route_prefix' => '/openapi',
                'host_forbidden' => [],
                'scan_paths' => [],
            ],
            $config
        );

        $hostForbiddenMiddleware = new HostForbiddenMiddleware($config['host_forbidden']);
        $openapiController = new OpenapiController([
            'scan_paths' => $config['scan_paths'] ?? [],
        ]);

        Route::get($config['route_prefix'], function () use ($openapiController) {
            return $openapiController->index();
        })->middleware($hostForbiddenMiddleware);
        Route::get("{$config['route_prefix']}/doc", function () use ($openapiController) {
            return $openapiController->doc();
        })->middleware($hostForbiddenMiddleware);
    }
}