<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

use InvalidArgumentException;
use support\Container;
use WebmanTech\Swagger\DTO\BaseDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * @property string $desc
 * @property string $method
 * @property string $path
 * @property string $controller
 * @property string $action
 * @property null|string $name
 * @property null|array<int, mixed>|string $middlewares
 */
class RouteConfigDTO extends BaseDTO
{
    /**
     * @deprecated
     */
    public const X_NAME = SchemaConstants::X_NAME;
    /**
     * @deprecated
     */
    public const X_PATH = SchemaConstants::X_PATH;
    /**
     * @deprecated
     */
    public const X_MIDDLEWARE = SchemaConstants::X_MIDDLEWARE;

    /**
     * @deprecated
     */
    public const MIDDLEWARE_NAMED_PREFIX = SchemaConstants::MIDDLEWARE_NAMED_PREFIX;

    public function initData(): void
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
        /* @phpstan-ignore-next-line */
        if (is_array($this->middlewares)) {
            $temp = [];
            foreach ($this->middlewares as $middleware) {
                /** @phpstan-ignore-next-line */
                $temp[] = $this->formatMiddleware($middleware);
            }
            return array_filter($this->middlewares = $temp);
        }

        /* @phpstan-ignore-next-line */
        throw new InvalidArgumentException('Invalid middlewares type');
    }

    /**
     * @param string|array $middleware
     * @return null|string|array|callable
     */
    private function formatMiddleware($middleware)
    {
        if (is_string($middleware)) {
            if (str_starts_with($middleware, SchemaConstants::MIDDLEWARE_NAMED_PREFIX)) {
                $name = substr($middleware, strlen(SchemaConstants::MIDDLEWARE_NAMED_PREFIX));
                $middleware = static::getNamedMiddleware($name);
            }
            return $middleware;
        }
        if (is_array($middleware)) {
            return fn() => Container::make($middleware[0], (array)($middleware[1] ?? []));
        }

        /** @phpstan-ignore-next-line */
        throw new InvalidArgumentException('Invalid middleware type');
    }

    private static array $namedMiddlewares = [];

    /**
     * 设置命名的路由中间件
     * @param string $name
     * @param string|array|callable $middleware
     * @return void
     */
    public static function registerNamedMiddleware(string $name, $middleware): void
    {
        self::$namedMiddlewares[$name] = $middleware;
    }

    /**
     * 获取命名的路由中间件
     * @param string $name
     * @return null|string|array|callable
     */
    public static function getNamedMiddleware(string $name)
    {
        return self::$namedMiddlewares[$name] ?? null;
    }
}
