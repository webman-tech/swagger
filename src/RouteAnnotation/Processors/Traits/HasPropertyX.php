<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors\Traits;

use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;

trait HasPropertyX
{
    private function getPropertyXValue(AnProperty $property, string $key): ?string
    {
        $value = null;
        if (!Generator::isDefault($property->x) && array_key_exists($key, $property->x)) {
            $value = $property->x[$key];
            //unset($property->x[$key]); // 不能清理，有些基础类会复用
        }

        return $value;
    }

    private function fixSchemaRequiredWithPropertyRequired(AnProperty $property, array $schemaRequired, ?bool $required): array
    {
        if ($required !== null) {
            // 根据 property 上定义的 x.required ，补全或者提出掉 schema 上的 required
            $isInSchemaRequired = in_array($property->property, $schemaRequired, true);
            if ($required && !$isInSchemaRequired) {
                $schemaRequired[] = $property->property;
            }
            if (!$required && $isInSchemaRequired) {
                $schemaRequired = array_filter($schemaRequired, fn($item) => $item !== $property->property);
            }
        }

        return $schemaRequired;
    }

    private function renewSchemaWithProperty(AnProperty $property): Schema
    {
        $schema = clone $property;
        $schema->schema = Generator::UNDEFINED;
        $schema->property = Generator::UNDEFINED;

        return new Schema(
            description: Generator::isDefault($schema->description) ? null : $schema->description,
            type: Generator::isDefault($schema->type) ? null : $schema->type,
            format: Generator::isDefault($schema->format) ? null : $schema->format,
            items: Generator::isDefault($schema->items) ? null : $schema->items,
            default: Generator::isDefault($schema->default) ? null : $schema->default,
            maximum: Generator::isDefault($schema->maximum) ? null : $schema->maximum,
            minimum: Generator::isDefault($schema->minimum) ? null : $schema->minimum,
            maxLength: Generator::isDefault($schema->maxLength) ? null : $schema->maxLength,
            minLength: Generator::isDefault($schema->minLength) ? null : $schema->minLength,
            pattern: Generator::isDefault($schema->pattern) ? null : $schema->pattern,
            enum: Generator::isDefault($schema->enum) ? null : $schema->enum,
            example: Generator::isDefault($schema->example) ? null : $schema->example,
            nullable: Generator::isDefault($schema->nullable) ? null : $schema->nullable,
            additionalProperties: Generator::isDefault($schema->additionalProperties) ? null : $schema->additionalProperties,
        );
    }
}
