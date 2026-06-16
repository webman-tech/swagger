<?php

namespace WebmanTech\Swagger\Overwrite;

use OpenApi\Annotations as OA;

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
    public function getAnnotationForSource(string $fqdn, string $sourceClass = OA\Schema::class): ?OA\AbstractAnnotation
    {
        $schema = parent::getAnnotationForSource($fqdn, $sourceClass);
        if ($this->schemaNameFormatter && $schema && $schema->isRoot(OA\Schema::class)) {
            call_user_func($this->schemaNameFormatter, $schema);
        }
        return $schema;
    }
}
