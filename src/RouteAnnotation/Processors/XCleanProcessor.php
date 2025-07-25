<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation\DTO\XInPropertyDTO;

/**
 * 清理 schema 上的特殊使用的 x 参数
 */
final class XCleanProcessor
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);
        foreach ($operations as $operation) {
            SwaggerHelper::removeAnnotationXValue($operation, [
                SchemaConstants::X_NAME,
                SchemaConstants::X_PATH,
                SchemaConstants::X_MIDDLEWARE,
            ]);
        }

        /** @var AnSchema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(AnSchema::class);
        foreach ($schemas as $schema) {
            XInPropertyDTO::removeFromSchema($schema);
        }

        /** @var AnProperty[] $properties */
        $properties = $analysis->getAnnotationsOfType(AnProperty::class);
        foreach ($properties as $property) {
            SwaggerHelper::removeAnnotationXValue($property, SchemaConstants::X_PROPERTY_TYPES);
        }
    }
}
