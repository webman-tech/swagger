<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

use InvalidArgumentException;
use support\Container;
use WebmanTech\Swagger\DTO\BaseDTO;

/**
 * @property string $desc
 * @property string $method
 * @property string $path
 * @property string $controller
 * @property string $action
 * @property null|string $name
 * @property null|array<int, string>|string $middlewares
 */
class RouteConfigDTO extends BaseDTO
{
    /**
     * 命名路由
     */
    public const X_NAME = 'route-name';
    /**
     * 路由 path
     * 当 openapi 上的 path 不能满足时路由定义时使用
     * 比如 /user/{id:\d+} 或 /user[/{name}]，可以通过此设置
     */
    public const X_PATH = 'route-path';
    /**
     * 路由中间件
     * @see self::getRouteMiddlewares()
     */
    public const X_MIDDLEWARE = 'route-middleware';

    /**
     * 命名路由的前缀
     */
    public const MIDDLEWARE_NAMED_PREFIX = '@named:';

    public function initData()
    {
        $this->_data = array_merge([
            'desc' => '',
            'method' => '',
            'path' => '',
            'controller' => '',
            'action' => '',
            'name' => null,
            'middlewares' => null,
        ], $this->_data);
    }

    /**
     * 获取 webman 适用的中间件
     * @return array|null
     */
    public function getRouteMiddlewares(): ?array
    {
        if (is_null($this->middlewares)) {
            return null;
        }
        if (is_string($this->middlewares)) {
            return array_filter([$this->formatMiddleware($this->middlewares)]);
        }
        if (is_array($this->middlewares)) {
            $temp = [];
            foreach ($this->middlewares as $middleware) {
                $temp[] = $this->formatMiddleware($middleware);
            }
            return array_filter($this->middlewares = $temp);
        }

        throw new InvalidArgumentException('Invalid middlewares type');
    }

    private function formatMiddleware($middleware)
    {
        if (is_string($middleware)) {
            if (strpos($middleware, static::MIDDLEWARE_NAMED_PREFIX) === 0) {
                $name = substr($middleware, strlen(static::MIDDLEWARE_NAMED_PREFIX));
                $middleware = static::getNamedMiddleware($name);
            }
            return $middleware;
        }
        if (is_array($middleware)) {
            return function () use ($middleware) {
                return Container::make($middleware[0], $middleware[1] ?? []);
            };
        }

        throw new InvalidArgumentException('Invalid middleware type');
    }

    private static $namedMiddlewares = [];

    /**
     * 设置命名的路由中间件
     * @param string $name
     * @param string|array|callable $middleware
     * @return void
     */
    public static function registerNamedMiddleware(string $name, $middleware): void
    {
        static::$namedMiddlewares[$name] = $middleware;
    }

    /**
     * 获取命名的路由中间件
     * @param string $name
     * @return null|string|array|callable
     */
    public static function getNamedMiddleware(string $name)
    {
        return static::$namedMiddlewares[$name] ?? null;
    }
}
