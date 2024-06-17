<?php

namespace Tests\Fixtures;

use OpenApi\Attributes as OA;
use Tests\Fixtures\SchemaNested;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;

/**
 * 嵌套用，带嵌套
 */
class SchemaNestedWithNested extends BaseSchema
{
    #[OA\Property]
    public string $string;
    #[OA\Property]
    public int $int;
    #[OA\Property]
    public SchemaNested $nested;
}