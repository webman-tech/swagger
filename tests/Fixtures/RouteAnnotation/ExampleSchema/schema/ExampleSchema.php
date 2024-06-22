<?php

namespace Tests\Fixtures\RouteAnnotation\ExampleSchema\schema;

use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;
use OpenApi\Attributes as OA;

#[OA\Schema]
class ExampleSchema extends BaseSchema
{
    #[OA\Property(description: '用户名', example: 'admin')]
    public string $username;
    #[OA\Property(description: '密码', example: '123456')]
    public string $password;
    #[OA\Property(description: '名称', example: '测试用户')]
    public string $name;
    #[OA\Property(description: '状态', example: 0)]
    public int $status;
}

#[OA\Schema(required: ['username', 'password', 'name'])]
class ExampleCreateSchema extends BaseSchema
{
    #[OA\Property(description: '用户名', maxLength: 64, example: 'admin')]
    public string $username;
    #[OA\Property(description: '密码', maxLength: 64, example: '123456')]
    public string $password;
    #[OA\Property(description: '名称', maxLength: 64, example: '测试用户')]
    public string $name;
}

#[OA\Schema]
class ExampleUpdateSchema extends BaseSchema
{
    #[OA\Property(description: '用户名', maxLength: 64, example: 'admin')]
    public string $username;
    #[OA\Property(description: '密码', maxLength: 64, example: '123456')]
    public string $password;
    #[OA\Property(description: '名称', maxLength: 64, example: '测试用户')]
    public string $name;
    #[OA\Property(description: '状态', example: 0)]
    public int $status;
}