<?php

namespace Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class SampleMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(Request $request, callable $handler): Response
    {
        $middlewares = $request->middlewares ?? [];
        $middlewares[] = __CLASS__;
        $request->middlewares = $middlewares;

        return $handler($request);
    }
}