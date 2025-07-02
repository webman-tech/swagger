<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Generator;
use WebmanTech\Swagger\Helper\SwaggerHelper;

final class AugmentSchemas extends \OpenApi\Processors\AugmentSchemas
{
    protected function augmentSchema(array $schemas): void
    {
        foreach ($schemas as $schema) {
            if (!$schema->isRoot(AnSchema::class)) {
                continue;
            }
            if (Generator::isDefault($schema->schema)) {
                SwaggerHelper::fillSchemaAttributeSchema($schema);
            }
        }
    }
}
