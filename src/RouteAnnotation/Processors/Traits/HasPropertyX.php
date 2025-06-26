<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors\Traits;

use OpenApi\Annotations\Property;
use OpenApi\Generator;

trait HasPropertyX
{
    private function getPropertyXValue(Property $property, string $key): ?string
    {
        $value = null;
        if (!Generator::isDefault($property->x) && array_key_exists($key, $property->x)) {
            $value = $property->x[$key];
            //unset($property->x[$key]); // 不能清理，有些基础类会复用
        }

        return $value;
    }

    private function fixSchemaRequiredWithPropertyRequired(Property $property, array $schemaRequired, ?bool $required): array
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
}
