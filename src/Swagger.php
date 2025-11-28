<?php

namespace WebmanTech\Swagger;

use WebmanTech\CommonUtils\Route;
use WebmanTech\Swagger\Controller\OpenapiController;
use WebmanTech\Swagger\DTO\ConfigRegisterRouteDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Middleware\HostForbiddenMiddleware;
use WebmanTech\Swagger\RouteAnnotation\Reader;
use function WebmanTech\CommonUtils\app_path;

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
     * 根据配置注册路由
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
        $dtoGeneratorRoute = rtrim($swaggerRoute, '/') . '/dto-generator';

        $routerRegister = Route::getCurrent();

        if ($config->openapi_doc->enable_dto_generator && ConfigHelper::getDtoGeneratorPath() !== null) {
            $routeName = 'swagger.dto_generator';
            $dtoGeneratorConfig = $config->openapi_doc->dto_generator_config ?? [
                'defaultGenerationType' => 'form',
                'defaultNamespace' => 'app\\api\\controller\\form',
            ];
            $routerRegister->addRoute(new Route\RouteObject(
                'GET',
                $dtoGeneratorRoute,
                fn() => $controller->dtoGenerator($dtoGeneratorConfig),
                name: $routeName,
                middlewares: $hostForbiddenMiddleware,
            ));
            if ($url = $routerRegister->getRouteByName($routeName)->getUrl(appendPrefix: true)) {
                $config->swagger_ui->data['dto_generator_url'] = $url;
            }
        }

        // 注册 swagger 访问的路由
        $routerRegister->addRoute(new Route\RouteObject(
            'GET',
            $swaggerRoute,
            fn() => $controller->swaggerUI($docUrl, $config->swagger_ui),
            middlewares: $hostForbiddenMiddleware
        ));
        // 注册 openapi doc 的路由
        $routerRegister->addRoute(new Route\RouteObject(
            'GET',
            $docRoute,
            fn() => $controller->openapiDoc($config->openapi_doc),
            middlewares: $hostForbiddenMiddleware
        ));

        // 注册 api 接口路由
        if ($config->register_route) {
            $reader = new Reader();
            foreach ($reader->getData($config->openapi_doc->getScanSources()) as $item) {
                $routerRegister->addRoute(new Route\RouteObject(
                    $item->method,
                    $item->path,
                    [$item->controller, $item->action],
                    name: $item->name,
                    middlewares: $hostForbiddenMiddleware
                ));
            }
        }
    }
}
