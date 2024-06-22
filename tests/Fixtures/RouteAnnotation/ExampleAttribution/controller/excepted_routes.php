<?php

use Tests\Fixtures\RouteAnnotation\ExampleAttribution\controller\ExampleSourceController;
use Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware\SampleMiddleware;
use Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware\SampleMiddleware2;

return [
    'GET:/crud' => [
        'name' => 'crud.list',
        'method' => 'GET',
        'path' => '/crud',
        'callback' => [ExampleSourceController::class, 'index'],
        'middlewares' => [
            SampleMiddleware::class,
            /*[
                SampleMiddleware2::class,
                [
                    'param' => 'use params array'
                ]
            ],*/
        ]
    ],
    'POST:/crud' => [
        'name' => 'POST:/crud',
        'method' => 'POST',
        'path' => '/crud',
        'callback' => [ExampleSourceController::class, 'store'],
        'middlewares' => []
    ],
    'GET:/crud/{id:\d+}' => [
        'name' => 'GET:/crud/{id:\d+}',
        'method' => 'GET',
        'path' => '/crud/{id:\d+}',
        'callback' => [ExampleSourceController::class, 'show'],
        'middlewares' => []
    ],
    'PUT:/crud/{id}' => [
        'name' => 'PUT:/crud/{id}',
        'method' => 'PUT',
        'path' => '/crud/{id}',
        'callback' => [ExampleSourceController::class, 'update'],
        'middlewares' => []
    ],
    'DELETE:/crud/{id}' => [
        'name' => 'DELETE:/crud/{id}',
        'method' => 'DELETE',
        'path' => '/crud/{id}',
        'callback' => [ExampleSourceController::class, 'destroy'],
        'middlewares' => []
    ],
    'PUT:/crud/{id}/recovery' => [
        'name' => 'PUT:/crud/{id}/recovery',
        'method' => 'PUT',
        'path' => '/crud/{id}/recovery',
        'callback' => [ExampleSourceController::class, 'recovery'],
        'middlewares' => []
    ]
];