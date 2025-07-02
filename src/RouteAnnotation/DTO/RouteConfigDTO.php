<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

use InvalidArgumentException;
use support\Container;
use WebmanTech\Swagger\DTO\SchemaConstants;

final class RouteConfigDTO
{
    public function __construct(
        public string            $desc = '',
        public string            $method = '',
        public string            $path = '',
        public string            $controller = '',
        public string            $action = '',
        public null|string       $name = null,
        public null|string|array $middlewares = null,
    )
    {
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

    private function formatMiddleware(array|string $middleware): callable|array|string|null
    {
        if (is_string($middleware)) {
            if (str_starts_with($middleware, SchemaConstants::MIDDLEWARE_NAMED_PREFIX)) {
                $name = substr($middleware, strlen(SchemaConstants::MIDDLEWARE_NAMED_PREFIX));
                $middleware = self::getNamedMiddleware($name);
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
     */
    public static function registerNamedMiddleware(string $name, callable|array|string $middleware): void
    {
        self::$namedMiddlewares[$name] = $middleware;
    }

    /**
     * 获取命名的路由中间件
     */
    public static function getNamedMiddleware(string $name): callable|array|string|null
    {
        return self::$namedMiddlewares[$name] ?? null;
    }
}
