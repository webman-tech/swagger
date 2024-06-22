<?php

namespace Tests\Fixtures\RouteAnnotation\ExampleSchema\controller;

use OpenApi\Attributes as OA;
use support\Request;
use support\Response;
use Tests\Fixtures\RouteAnnotation\ExampleSchema\schema\ExampleCreateSchema;
use Tests\Fixtures\RouteAnnotation\ExampleSchema\schema\ExampleSchema;
use Tests\Fixtures\RouteAnnotation\ExampleSchema\schema\ExampleUpdateSchema;

#[OA\Tag(name: 'crud', description: 'crud 例子')]
class ExampleSourceController
{
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