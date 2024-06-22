<?php

namespace Tests\Fixtures\RouteAnnotation\ExampleAttribution\controller;

use OpenApi\Attributes as OA;
use support\Request;
use support\Response;
use Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware\SampleMiddleware;
use Tests\Fixtures\RouteAnnotation\ExampleAttribution\middleware\SampleMiddleware2;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;

#[OA\Tag(name: 'crud', description: 'crud 例子')]
class ExampleSourceController
{
    #[OA\Get(
        path: '/crud',
        summary: '列表',
        security: [
            ['api_key' => []]
        ],
        tags: ['crud'],
        parameters: [
            new OA\Parameter(name: 'page', description: '页数', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', description: '每页数量', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'username', description: '用户名', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', description: '状态', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '列表数据'),
        ],
        x: [
            // 指定中间件，支持各种方式
            RouteConfigDTO::X_MIDDLEWARE => [
                SampleMiddleware::class,
                [SampleMiddleware2::class, ['param' => 'use params array']],
                RouteConfigDTO::MIDDLEWARE_NAMED_PREFIX . 'sample_middleware3',
            ],
            // 指定路由别名
            RouteConfigDTO::X_NAME => 'crud.list',
        ],
    )]
    public function index(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
            'middlewares' => $request->middlewares ?? [],
            'route' => route('crud.list'),
        ]);
    }

    #[OA\Get(
        path: '/crud/{id}',
        summary: '详情',
        security: [
            ['api_key' => []]
        ],
        tags: ['crud'],
        parameters: [
            new OA\PathParameter(name: 'id', description: 'ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '明细'),
        ],
        x: [
            // 特殊的路由路径
            RouteConfigDTO::X_PATH => '/crud/{id:\d+}',
        ]
    )]
    public function show(Request $request, int $id): Response
    {
        return \json([
            'action' => __FUNCTION__,
            'route' => $request->route->getPath(),
        ]);
    }

    #[OA\Post(
        path: '/crud',
        summary: '新建',
        security: [
            ['api_key' => []]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\JsonContent(
                    required: ['username', 'password', 'name'],
                    properties: [
                        new OA\Property(property: 'username', description: '用户名', type: 'string', maxLength: 64, example: 'admin'),
                        new OA\Property(property: 'password', description: '密码', type: 'string', maxLength: 64, example: '123456'),
                        new OA\Property(property: 'name', description: '名称', type: 'string', example: '测试用户'),
                    ],
                    type: 'object'
                )
            ]
        ),
        tags: ['crud'],
        responses: [
            new OA\Response(response: 200, description: '新建后的明细'),
        ],
    )]
    public function store(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }

    #[OA\Put(
        path: '/crud/{id}',
        summary: '更新',
        security: [
            ['api_key' => []]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'username', description: '用户名', type: 'string', maxLength: 64, example: 'admin'),
                            new OA\Property(property: 'password', description: '密码', type: 'string', maxLength: 64, example: '123456'),
                            new OA\Property(property: 'name', description: '名称', type: 'string', example: '测试用户'),
                            new OA\Property(property: 'status', description: '状态', type: 'integer', example: 0),
                        ],
                        type: 'object'
                    ),
                )
            ]
        ),
        tags: ['crud'],
        responses: [
            new OA\Response(response: 200, description: '更新后的明细'),
        ],
    )]
    public function update(Request $request, int $id): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }

    #[OA\Delete(
        path: '/crud/{id}',
        summary: '删除',
        security: [
            ['api_key' => []]
        ],
        tags: ['crud'],
        parameters: [
            new OA\PathParameter(name: 'id', description: 'ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '无返回数据'),
        ],
    )]
    public function destroy(Request $request, int $id): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }

    #[OA\Put(
        path: '/crud/{id}/recovery',
        summary: '恢复',
        security: [
            ['api_key' => []]
        ],
        tags: ['crud'],
        parameters: [
            new OA\PathParameter(name: 'id', description: 'ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '明细'),
        ]
    )]
    public function recovery(Request $request, int $id): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }
}