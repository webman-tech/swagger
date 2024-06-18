<?php

namespace Tests\Fixtures;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;

/**
 * 各种通过类型定义和注解定义的字段方式
 */
#[OA\Schema(required: ['string', 'bool'])]
class SchemaExampleTypes extends BaseSchema
{
    #[OA\Property]
    public string $string; // 通过属性类型定义 string
    #[OA\Property(type: 'string')]
    public $string2; // 通过注解定义 string
    #[OA\Property]
    public int $int; // 通过属性类型定义 int
    #[OA\Property(type: 'integer')]
    public int $int2; // 通过注解定义 integer
    #[OA\Property]
    public bool $bool; // 通过属性类型定义 bool
    #[OA\Property(type: 'boolean')]
    public bool $bool2; // 通过注解定义 boolean

    #[OA\Property]
    public float $float; // 通过属性类型定义 float
    #[OA\Property(type: 'number')]
    public $number; // 通过注解定义 number

    #[OA\Property]
    public string|int $union; // 通过属性类型定义 联合类型
    #[OA\Property(type: ['string', 'integer'])]
    public $union2; // 通过注解定义 联合类型

    #[OA\Property]
    public SchemaNestedHasRequired $object; // 通过属性类型定义 对象
    #[OA\Property(ref: SchemaNestedHasRequired::class)]
    public $object2; // 通过注解定义 对象
    #[OA\Property]
    public SchemaNestedWithNested $object3;

    #[OA\Property(items: new OA\Items(ref: SchemaNested::class))]
    public array $array; // 通过属性类型定义 数组
    #[OA\Property(type: 'array', items: new OA\Items(ref: SchemaNested::class))]
    public $array2; // 通过注解定义 数组
    #[OA\Property]
    public array $array3; // 通过属性类型定义 数组 但是未通过注解定义 items

    public string $notExist; // 忽略未通过注解定义的属性

    #[OA\Property]
    public mixed $mixed; // 通过属性类型定义 mixed
    #[OA\Property]
    public $mixed2; // 未定义类型，解析为 mixed

    #[OA\Property]
    public ?string $nullable; // 通过属性类型定义 nullable
    #[OA\Property(nullable: true)]
    public $nullable2; // 通过注解定义 nullable
}
