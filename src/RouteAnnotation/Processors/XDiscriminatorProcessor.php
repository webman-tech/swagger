<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Components;
use OpenApi\Attributes\Discriminator;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\DTO\Attributes\ValidationRulesDiscriminator;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * 处理 x-discriminator 配置
 * 将包含 x-discriminator 的 Schema 改造为符合 OpenAPI 规范的 oneOf + discriminator 结构
 */
final class XDiscriminatorProcessor
{
    private Analysis $analysis;

    public function __invoke(Analysis $analysis): void
    {
        $this->analysis = $analysis;
        $this->processSchemas($analysis);
    }

    private function processSchemas(Analysis $analysis): void
    {
        /** @var AnSchema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(AnSchema::class);
        $schemas = array_filter($schemas, fn(AnSchema $schema) => in_array($schema::class, [AnSchema::class, Schema::class]));

        foreach ($schemas as $schema) {
            $this->transformSchema($schema);
        }
    }

    private function transformSchema(AnSchema $schema): void
    {
        if (Generator::isDefault($schema->properties)) {
            return;
        }

        // 查找包含 x-discriminator 的属性
        foreach ($schema->properties as $property) {
            $xDiscriminator = SwaggerHelper::getAnnotationXValue($property, SchemaConstants::X_PROPERTY_DISCRIMINATOR);
            if ($xDiscriminator) {
                $xDiscriminator = ValidationRulesDiscriminator::fromData($xDiscriminator);
                $this->applyDiscriminator($schema, $property, $xDiscriminator);
                break; // 只处理第一个 discriminator
            }
        }
    }

    private function applyDiscriminator(AnSchema $schema, AnProperty $targetProperty, ValidationRulesDiscriminator $discriminator): void
    {
        // 获取原始 schema 的名称
        $baseSchemaName = SwaggerHelper::getValue($schema->schema);
        if (!$baseSchemaName) {
            return;
        }

        // 生成 oneOf 数组和独立的 Schema
        $oneOfSchemas = [];
        $discriminatorMapping = [];

        foreach ($discriminator->mapping as $mappingValue => $dtoClass) {
            if (!class_exists($dtoClass)) {
                continue;
            }

            $childSchema = $this->analysis->getSchemaForSource($dtoClass);
            if (!$childSchema) {
                continue;
            }

            // 生成新的 Schema 名称：OrderDTO_type_normal
            $variantSchemaName = $baseSchemaName . '_type_' . $mappingValue;

            // 创建一个独立的 Schema
            $variantSchema = new Schema(
                schema: $variantSchemaName,
                required: [],
                properties: [],
                type: 'object',
            );

            // 复制原始 Schema 的所有属性
            foreach ($schema->properties as $property) {
                if ($property === $targetProperty) {
                    // 替换为具体的 ref
                    $variantSchema->properties[] = new Property(
                        property: SwaggerHelper::getValue($property->property),
                        ref: Components::ref($childSchema),
                    );
                } else {
                    // 复制属性
                    $propClone = clone $property;
                    // 如果是 discriminator 字段，设置固定值
                    if (SwaggerHelper::getValue($property->property) === $discriminator->property) {
                        $propClone->enum = [$mappingValue];
                    }
                    $variantSchema->properties[] = $propClone;
                }
            }

            // 复制 required 字段
            $originalRequired = SwaggerHelper::getValue($schema->required, []);
            if ($originalRequired) {
                $variantSchema->required = $originalRequired;
            }

            // 添加到 OpenAPI 的 components 中
            $openApi = $this->analysis->openapi;
            if ($openApi === null) {
                return;
            }
            if (Generator::isDefault($openApi->components)) {
                $openApi->components = new Components();
            }
            if (Generator::isDefault($openApi->components->schemas)) {
                $openApi->components->schemas = [];
            }
            $openApi->components->schemas[$variantSchemaName] = $variantSchema;

            // oneOf 使用 ref 引用
            $oneOfSchemas[] = new Schema(ref: Components::ref($variantSchemaName));

            // 构建 discriminator mapping
            $discriminatorMapping[$mappingValue] = Components::ref($variantSchemaName);
        }

        if (empty($oneOfSchemas)) {
            return;
        }

        // 清空原始 properties 和 required
        $schema->properties = [];
        $schema->required = [];

        // 设置 oneOf
        $schema->oneOf = $oneOfSchemas;

        // 设置 discriminator
        $schema->discriminator = new Discriminator(
            propertyName: $discriminator->property,
            mapping: $discriminatorMapping,
        );
    }
}
