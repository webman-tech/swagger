<?php

namespace WebmanTech\Swagger\Integrations;

use InvalidArgumentException;
use Webman\Http\Response as WebmanResponse;
use WebmanTech\Swagger\Helper\ConfigHelper;

/**
 * @internal
 */
final class Response
{
    private static ?ResponseInterface $factory = null;

    public static function create(): ResponseInterface
    {
        if (self::$factory === null) {
            $factory = ConfigHelper::get('app.response_factory');
            if ($factory === null) {
                $factory = match (true) {
                    class_exists(WebmanResponse::class) => WebmanResponseFactory::class,
                    default => throw new InvalidArgumentException('not found response class'),
                };
            }
            if ($factory instanceof \Closure) {
                $factory = $factory();
            }
            if ($factory instanceof ResponseInterface) {
                self::$factory = $factory;
            } elseif (class_exists($factory) && is_a($factory, ResponseInterface::class, true)) {
                self::$factory = new $factory();
            } else {
                throw new InvalidArgumentException('response_factory error');
            }
        }

        return self::$factory;
    }
}

/**
 * @internal
 */
final class WebmanResponseFactory implements ResponseInterface
{
    public function renderView(string $view, array $data, string $viewPath): WebmanResponse
    {
        return raw_view($view, $data, $viewPath);
    }

    public function body(string $content, array $header): mixed
    {
        return new WebmanResponse(
            headers: $header,
            body: $content,
        );
    }
}
