<?php

namespace Tests\Fixtures;

use OpenApi\Attributes as OA;
use Webman\Http\UploadFile;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;

/**
 * 文件类型的
 */
#[OA\Schema(required: ['string', 'bool'])]
class SchemaExampleFile extends BaseSchema
{
    #[OA\Property]
    public UploadFile $file;
    #[OA\Property(type: 'string', format: 'binary')]
    public $file2;
}

