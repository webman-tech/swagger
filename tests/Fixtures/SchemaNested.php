<?php

namespace Tests\Fixtures;

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;

/**
 * 嵌套用，不带 required
 */
class SchemaNested extends BaseSchema
{
    #[OA\Property]
    public string $string;
    #[OA\Property]
    public int $int;
}