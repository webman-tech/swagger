<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use WebmanTech\DTO\BaseDTO;

/**
 * @deprecated 使用 BaseDTO、BaseRequestDTO ... 代替
 */
class BaseSchema extends BaseDTO
{
    public static function create(array $data)
    {
        return static::fromData($data);
    }
}
