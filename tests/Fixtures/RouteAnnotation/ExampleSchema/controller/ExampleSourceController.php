<?php

namespace Tests\Fixtures\RouteAnnotation\ExampleSchema\controller;

use OpenApi\Attributes as OA;
use support\Request;
use support\Response;
use Tests\Fixtures\RouteAnnotation\ExampleSchema\schema\ExampleCreateSchema;
use Tests\Fixtures\RouteAnnotation\ExampleSchema\schema\ExampleSchema;
use Tests\Fixtures\RouteAnnotation\ExampleSchema\schema\ExampleUpdateSchema;
use WebmanTech\Swagger\DTO\SchemaConstants;

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
        ],
        responses: [
            new OA\Response(response: 200, description: '列表数据'),
        ],
        x: [
            SchemaConstants::X_SCHEMA_TO_PARAMETERS => ExampleSchema::class,
        ]
    )]
    public function index(Request $request): Response
    {
        return \json([
            'action' => __FUNCTION__,
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
                new OA\JsonContent(ref: ExampleCreateSchema::class)
            ]
        ),
        tags: ['crud'],
        responses: [
            new OA\Response(response: 200, description: '新建后的明细', content: new OA\JsonContent(ref: ExampleSchema::class)),
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
                new OA\JsonContent(ref: ExampleUpdateSchema::class)
            ]
        ),
        tags: ['crud'],
        responses: [
            new OA\Response(response: 200, description: '更新后的明细', content: new OA\JsonContent(ref: ExampleSchema::class)),
        ],
    )]
    public function update(Request $request, int $id): Response
    {
        return \json([
            'action' => __FUNCTION__,
        ]);
    }
}