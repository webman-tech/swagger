<?php

namespace WebmanTech\Swagger\Overwrite;

use OpenApi\Annotations\Schema as AnSchema;

/**
 * @internal
 */
final class Analysis extends \OpenApi\Analysis
{
    private null|\Closure $schemaNameFormatter = null;

    public function setSchemaNameFormatter(\Closure $formatter): void
    {
        $this->schemaNameFormatter = $formatter;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaForSource(string $fqdn): ?AnSchema
    {
        $schema = parent::getSchemaForSource($fqdn);
        if ($this->schemaNameFormatter && $schema && $schema->isRoot(AnSchema::class)) {
            call_user_func($this->schemaNameFormatter, $schema);
        }
        return $schema;
    }
}
