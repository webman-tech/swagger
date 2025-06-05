<?php

namespace WebmanTech\Swagger\RouteAnnotation;

use Webman\Route;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;

class Register
{
    /**
     * @var array|RouteConfigDTO[]
     */
    private $config;

    /**
     * @param RouteConfigDTO|RouteConfigDTO[]|array[] $config
     */
    public function __construct($config)
    {
        if ($config instanceof RouteConfigDTO) {
            $config = [$config];
        } elseif (is_array($config)) {
            $firstValue = reset($config);
            if (!$firstValue instanceof RouteConfigDTO) {
                $config = array_map(function ($item) {
                    return new RouteConfigDTO($item);
                }, $config);
            }
        }
        $this->config = $config;
    }

    public function registerRoute(): void
    {
        foreach ($this->config as $key => $routeConfig) {
            Route::add($routeConfig->method, $routeConfig->path, [$routeConfig->controller, $routeConfig->action])
                ->name($routeConfig->name ?: $key)
                ->middleware($routeConfig->getRouteMiddlewares());
        }
    }
}
