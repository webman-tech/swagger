<?php

namespace WebmanTech\Swagger\Overwrite\Processors;

use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Generator;

/**
 * @internal
 */
final class AugmentSchemas extends \OpenApi\Processors\AugmentSchemas
{
    public function __construct(private readonly \Closure $schemaNameFormatter)
    {
    }

    protected function augmentSchema(array $schemas): void
    {
        foreach ($schemas as $schema) {
            if (!$schema->isRoot(AnSchema::class)) {
                continue;
            }
            if (Generator::isDefault($schema->schema)) {
                call_user_func($this->schemaNameFormatter, $schema);
            }
        }
    }
}
