<?php

namespace WebmanTech\Swagger\Integrations;

use InvalidArgumentException;
use Webman\MiddlewareInterface as WebmanMiddlewareInterface;
use WebmanTech\Swagger\DTO\ConfigHostForbiddenDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Integrations\Webman\HostForbiddenMiddleware;

/**
 * @internal
 */
class Middleware
{
    private static ?MiddlewareInterface $factory = null;

    public static function create(): MiddlewareInterface
    {
        if (self::$factory === null) {
            $factory = ConfigHelper::get('app.middleware_factory');
            if ($factory === null) {
                $factory = match (true) {
                    interface_exists(WebmanMiddlewareInterface::class) => WebmanMiddlewareFactory::class,
                    default => throw new InvalidArgumentException('not found middleware class'),
                };
            }
            if ($factory instanceof \Closure) {
                $factory = $factory();
            }
            if ($factory instanceof ResponseInterface) {
                self::$factory = $factory;
            } elseif (class_exists($factory)) {
                self::$factory = new $factory();
            } else {
                throw new InvalidArgumentException('response_middleware error');
            }
        }

        return self::$factory;
    }
}


/**
 * @internal
 */
final class WebmanMiddlewareFactory implements MiddlewareInterface
{
    public function makeHostForbiddenMiddleware(ConfigHostForbiddenDTO $config): HostForbiddenMiddleware
    {
        return new HostForbiddenMiddleware($config);
    }
}
