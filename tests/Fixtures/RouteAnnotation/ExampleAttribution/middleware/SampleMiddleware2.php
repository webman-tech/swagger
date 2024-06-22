<?php

namespace Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class SampleMiddleware2 implements MiddlewareInterface
{
    private string $param;

    public function __construct(string $param)
    {
        $this->param = $param;
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $handler): Response
    {
        $middlewares = $request->middlewares ?? [];
        $middlewares[] = __CLASS__ . ':' . $this->param;
        $request->middlewares = $middlewares;

        return $handler($request);
    }
}