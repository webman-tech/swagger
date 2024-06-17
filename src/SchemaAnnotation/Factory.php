<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use WebmanTech\Swagger\DTO\ConfigSchemaValidatorDTO;

class Factory
{
    /**
     * @param array|ConfigSchemaValidatorDTO $config
     */
    public function __construct(ReflectionClassReader $reader)
    {
        if (!$config instanceof ConfigSchemaValidatorDTO) {
            $config = new ConfigSchemaValidatorDTO($config);
        }
        $this->config = $config;
    }
}