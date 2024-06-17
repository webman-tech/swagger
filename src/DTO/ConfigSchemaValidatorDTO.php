<?php

namespace WebmanTech\Swagger\DTO;

use Illuminate\Contracts\Validation\Factory;
use WebmanTech\Swagger\SchemaAnnotation\ReflectionClassReader;

/**
 * @property ReflectionClassReader $reader
 * @property Factory $validator
 */
class ConfigSchemaValidatorDTO extends BaseDTO
{

}