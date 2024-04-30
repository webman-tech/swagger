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
 * @property array<string, <string, RequestParamDTO>> $request_param
 * @property array<string, <string, RequestBodyDTO>> $request_body
 * @property bool $request_body_required
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
            'request_param' => [],
            'request_body' => [],
            'request_body_required' => false,
        ], $this->_data);

        foreach ($this->request_param as $in => $value) {
            foreach ($value as $name => $config) {
                if (is_array($config)) {
                    $tempValue = $this->request_param;
                    $tempValue[$in][$name] = new RequestParamDTO($config);
                    $this->request_param = $tempValue;
                }
            }
        }

        foreach ($this->request_body as $mediaType => $value) {
            foreach ($value as $name => $config) {
                if (is_array($config)) {
                    $tempValue = $this->request_body;
                    $tempValue[$mediaType][$name] = new RequestBodyDTO($config);
                    $this->request_body = $tempValue;
                }
            }
            unset($v);
        }
        unset($value);
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
