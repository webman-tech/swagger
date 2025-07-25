<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Enums\PropertyInEnum;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation\DTO\XInPropertyDTO;

/**
 * 处理 property 上的 x-in 参数
 */
final class XSchemaPropertyInProcessor
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var AnSchema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(AnSchema::class);

        foreach ($schemas as $schema) {
            $this->transferPropertyIn($schema);
        }
    }

    private function transferPropertyIn(AnSchema $schema, ?string $refPrefix = null): void
    {
        $refPrefix ??= Components::ref($schema);
        foreach (SwaggerHelper::getValue($schema->allOf, []) as $index => $item) {
            /** @phpstan-ignore-next-line */
            if (!$item instanceof AnSchema) {
                continue;
            }
            $this->transferPropertyIn($item, Components::ref($schema) . '/allOf/' . $index);
        }
        foreach (SwaggerHelper::getValue($schema->oneOf, []) as $index => $item) {
            /** @phpstan-ignore-next-line */
            if (!$item instanceof AnSchema) {
                continue;
            }
            $this->transferPropertyIn($item, Components::ref($schema) . '/oneOf/' . $index);
        }

        $properties = [];
        $schemaRequired = SwaggerHelper::getValue($schema->required, []);
        foreach (SwaggerHelper::getValue($schema->properties, []) as $property) {
            /** @var AnProperty $property */
            $propertyIn = PropertyInEnum::tryFromPropertyX($property);
            if (!$propertyIn) {
                // 未单独设定 x-in 的，保留在原属性里
                $properties[] = $property;
                continue;
            }
            // 其他的情况转移到 x-in-property-data 里
            // 先移除 x-in
            SwaggerHelper::removeAnnotationXValue($property, SchemaConstants::X_PROPERTY_IN);
            // 检查必填字段，并且从 schema 的 required 中移除掉
            $isRequired = in_array($property->property, $schemaRequired, true);
            if ($isRequired) {
                // 移除 required 中的参数
                $schemaRequired = array_values(array_diff($schemaRequired, [$property->property]));
            }
            // 构造 DTO，设置到 schema 上
            $xPropertyIn = new XInPropertyDTO(
                in: $propertyIn,
                property: $property,
                schema: $schema,
                refFromPrefix: $refPrefix,
                required: $isRequired,
            );
            $xPropertyIn->set2Schema();
        }
        // 更新变更字段
        SwaggerHelper::setValue($schema->required, $schemaRequired);
        SwaggerHelper::setValue($schema->properties, $properties);
    }
}
