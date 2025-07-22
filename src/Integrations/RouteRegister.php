<?php

namespace WebmanTech\Swagger\Integrations;

use InvalidArgumentException;
use Webman\Route as WebmanRoute;
use WebmanTech\Swagger\Helper\ConfigHelper;

/**
 * @internal
 */
final class RouteRegister
{
    private static ?RouteRegisterInterface $factory = null;

    public static function create(): RouteRegisterInterface
    {
        if (self::$factory === null) {
            $factory = ConfigHelper::get('app.route_factory');
            if ($factory === null) {
                $factory = match (true) {
                    class_exists(WebmanRoute::class) => WebmanRouteFactory::class,
                    default => throw new InvalidArgumentException('not found route class'),
                };
            }
            if ($factory instanceof \Closure) {
                $factory = $factory();
            }
            if ($factory instanceof RouteRegisterInterface) {
                self::$factory = $factory;
            } elseif (class_exists($factory) && is_a($factory, RouteRegisterInterface::class, true)) {
                self::$factory = new $factory();
            } else {
                throw new InvalidArgumentException('route_factory error');
            }
        }

        return self::$factory;
    }
}

/**
 * @internal
 */
final class WebmanRouteFactory implements RouteRegisterInterface
{
    public function register(array $routes): void
    {
        foreach ($routes as $key => $routeConfig) {
            WebmanRoute::add($routeConfig->method, $routeConfig->path, [$routeConfig->controller, $routeConfig->action])
                ->name($routeConfig->name ?: $key)
                ->middleware($routeConfig->middlewares);
        }
    }

    public function addRoute(string $method, string $path, \Closure $callback, mixed $middlewares = null): void
    {
        WebmanRoute::add(strtoupper($method), $path, $callback)->middleware($middlewares);
    }
}
