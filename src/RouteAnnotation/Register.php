<?php

namespace WebmanTech\Swagger\RouteAnnotation;

use Webman\Route;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;

/**
 * 注册路由
 */
final readonly class Register
{
    public function __construct(
        /**
         * @var array<string, RouteConfigDTO>
         */
        private array $configs
    )
    {
    }

    public function registerRoute(): void
    {
        foreach ($this->configs as $key => $routeConfig) {
            Route::add($routeConfig->method, $routeConfig->path, [$routeConfig->controller, $routeConfig->action])
                ->name($routeConfig->name ?: $key)
                ->middleware($routeConfig->getRouteMiddlewares());
        }
    }
}
