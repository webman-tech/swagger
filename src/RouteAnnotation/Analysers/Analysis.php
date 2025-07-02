<?php

namespace WebmanTech\Swagger\RouteAnnotation\Analysers;

use OpenApi\Annotations\Schema as AnSchema;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * @internal
 */
final class Analysis extends \OpenApi\Analysis
{
    /**
     * @inheritDoc
     */
    public function getSchemaForSource(string $fqdn): ?AnSchema
    {
        $schema = parent::getSchemaForSource($fqdn);
        if ($schema) {
            SwaggerHelper::fillSchemaAttributeSchema($schema);
        }
        return $schema;
    }
}
