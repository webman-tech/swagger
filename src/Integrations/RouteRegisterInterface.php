<?php

namespace WebmanTech\Swagger\Integrations;

use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;

interface RouteRegisterInterface
{
    /**
     * 注册路由
     * @param array<string, RouteConfigDTO> $routes 路由配置
     */
    public function register(array $routes): void;

    /**
     * 注册一个路由
     */
    public function addRoute(string $method, string $path, \Closure $callback, mixed $middlewares = null, ?string $name = null): void;

    /**
     * 根据 name 获取 url
     */
    public function getUrlByName(string $name): ?string;
}
