<?php

use Tests\Fixtures\RouteAnnotation\ExampleAttribution\controller\ExampleSourceController;
use Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware\SampleMiddleware;
use Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware\SampleMiddleware2;

return [
    'GET:/crud' => [
        'desc' => '列表',
        'method' => 'GET',
        'path' => '/crud',
        'controller' => ExampleSourceController::class,
        'action' => 'index',
        'name' => 'crud.list',
        'middlewares' => [
            SampleMiddleware::class,
            [
                SampleMiddleware2::class,
                [
                    'param' => 'use params array'
                ]
            ],
            '@named:sample_middleware3'
        ]
    ],
    'POST:/crud' => [
        'desc' => '新建',
        'method' => 'POST',
        'path' => '/crud',
        'controller' => ExampleSourceController::class,
        'action' => 'store',
        'name' => null,
        'middlewares' => null
    ],
    'GET:/crud/{id:\d+}' => [
        'desc' => '详情',
        'method' => 'GET',
        'path' => '/crud/{id:\d+}',
        'controller' => ExampleSourceController::class,
        'action' => 'show',
        'name' => null,
        'middlewares' => null
    ],
    'PUT:/crud/{id}' => [
        'desc' => '更新',
        'method' => 'PUT',
        'path' => '/crud/{id}',
        'controller' => ExampleSourceController::class,
        'action' => 'update',
        'name' => null,
        'middlewares' => null
    ],
    'DELETE:/crud/{id}' => [
        'desc' => '删除',
        'method' => 'DELETE',
        'path' => '/crud/{id}',
        'controller' => ExampleSourceController::class,
        'action' => 'destroy',
        'name' => null,
        'middlewares' => null
    ],
    'PUT:/crud/{id}/recovery' => [
        'desc' => '恢复',
        'method' => 'PUT',
        'path' => '/crud/{id}/recovery',
        'controller' => ExampleSourceController::class,
        'action' => 'recovery',
        'name' => null,
        'middlewares' => null
    ]
];