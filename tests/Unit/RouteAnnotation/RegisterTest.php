<?php

use Webman\Route;
use WebmanTech\Swagger\RouteAnnotation\Register;

test('registerRoute', function () {
    $config = require get_path('/Fixtures/RouteAnnotation/ExampleAttribution/controller/excepted_config.php');

    $register = new Register($config);
    $register->registerRoute();

    $registeredRoutes = [];
    foreach (Route::getRoutes() as $route) {
        foreach ($route->getMethods() as $method) {
            $path = $route->getPath();
            $middlewares = array_filter($route->getMiddleware(), function ($middleware) {
                return is_string($middleware); // 暂时只支持一下字符串形式的中间件，callback 的不好校验
            });
            $registeredRoutes[$method.':'.$path] = [
                'name' => $route->getName(),
                'method' => $method,
                'path' => $path,
                'callback' => $route->getCallback(),
                'middlewares' => array_reverse($middlewares),
            ];
        }
    }

    $exceptedRoutes = require get_path('/Fixtures/RouteAnnotation/ExampleAttribution/controller/excepted_routes.php');

    expect($registeredRoutes)->toBe($exceptedRoutes);
});
