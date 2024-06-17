<?php

namespace Tests\Fixtures;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;

/**
 * 简化的各种类型参数
 */
#[OA\Schema(required: ['string', 'bool'])]
class SchemaExampleSimple extends BaseSchema
{
    #[OA\Property]
    public string $string = '';
    #[OA\Property(type: 'string')]
    public int $int = 0;
    #[OA\Property(type: 'integer')]
    public bool $bool = false;

    #[OA\Property]
    public float $float = 0;

    #[OA\Property]
    public string|int $union = 0;

    #[OA\Property]
    public ?SchemaNested $object = null;

    #[OA\Property(items: new OA\Items(ref: SchemaNestedHasRequired::class))]
    public array $array = []; // 通过属性类型定义 数组

    public string $notExist = '';

    #[OA\Property]
    public mixed $mixed = 1;

    #[OA\Property]
    public ?string $nullable = null;
}

