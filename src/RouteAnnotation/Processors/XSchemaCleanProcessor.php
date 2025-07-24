<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation\DTO\XInPropertyDTO;

/**
 * 清理 schema 上的特殊使用的 x 参数
 */
class XSchemaCleanProcessor
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var AnSchema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(AnSchema::class);
        foreach ($schemas as $schema) {
            XInPropertyDTO::removeFromSchema($schema);
        }

        $properties = $analysis->getAnnotationsOfType(AnProperty::class);
        foreach ($properties as $property) {
            SwaggerHelper::removeAnnotationXValue($property, SchemaConstants::X_PROPERTY_TYPES);
        }
    }
}
