<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\MediaType as AnMediaType;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Response as AnResponse;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;

// 支持给 response 支持包裹到一种 schema 结构中
final class ResponseLayoutProcessor
{
    public function __construct(
        private ?string $layoutClass = null,
        private ?string $layoutDataCode = null,
    )
    {
        if ($this->layoutDataCode === null) {
            $this->layoutDataCode = 'data';
        }
    }

    private Analysis $analysis;

    public function __invoke(Analysis $analysis): void
    {
        $this->analysis = $analysis;

        $globalLayoutSchema = $this->layoutClass ? $this->getLayoutSchema($this->layoutClass) : null;

        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);

        foreach ($operations as $operation) {
            $undefinedOperationLayoutKey = Generator::UNDEFINED;
            $layoutClass = SwaggerHelper::getAnnotationXValue($operation, SchemaConstants::X_RESPONSE_LAYOUT, $undefinedOperationLayoutKey, remove: true);
            if (
                ($layoutClass === $undefinedOperationLayoutKey && !$globalLayoutSchema) // 未配置且全局也没有
                || $layoutClass === null // 设置为 null，表示该不需要全局的 layout
            ) {
                continue;
            }
            $layoutSchema = $layoutClass === $undefinedOperationLayoutKey ? $globalLayoutSchema : $this->getLayoutSchema($layoutClass);
            $layoutDataCode = SwaggerHelper::getAnnotationXValue($operation, SchemaConstants::X_RESPONSE_LAYOUT_DATA_CODE, $this->layoutDataCode, remove: true);

            /** @var AnResponse|null $response */
            $response = SwaggerHelper::getValue($operation->responses, [])[200] ?? null;
            if (!$response) {
                continue;
            }
            /** @var AnMediaType $mediaType */
            $mediaType = $response->content['application/json'] ?? null;
            if (!$mediaType) {
                continue;
            }
            $schema = $mediaType->schema;
            if (!$schema) {
                continue;
            }

            $schemaAll = [
                new Schema(ref: Components::ref($layoutSchema)),
            ];

            if (!Generator::isDefault($schema->allOf)) {
                // 将 allOf 和 struct 合并
                foreach ($schema->allOf as $item) {
                    $schemaAll[] = $this->wrapperSchema($item, $layoutDataCode);
                }
            } elseif (!Generator::isDefault($schema->oneOf)) {
                // oneOf 只能单独
                $schemaAll[] = new Schema(
                    properties: [
                        new Property(
                            property: $layoutDataCode,
                            type: 'object',
                            oneOf: $schema->oneOf,
                        )
                    ],
                );
            } elseif (!Generator::isDefault($schema->properties) || !Generator::isDefault($schema->ref) || !Generator::isDefault($schema->items)) {
                // 单 schema 与 struct 合并
                $schemaAll[] = $this->wrapperSchema($schema, $layoutDataCode);
            }

            $mediaType->schema = new Schema(
                allOf: $schemaAll,
            );
        }
    }

    private function getLayoutSchema(string $layoutClass): AnSchema
    {
        $schema = $this->analysis->getSchemaForSource($layoutClass);
        if (!$schema) {
            throw new \InvalidArgumentException("layoutClass({$layoutClass}) must defined as schema（Not in scanned path?）");
        }
        return $schema;
    }

    private function wrapperSchema(AnSchema $schema, string $layoutDataCode): AnSchema
    {
        $newSchema = null;
        if (!Generator::isDefault($schema->ref)) {
            $newSchema = new Schema(
                properties: [
                    new Property(
                        property: $layoutDataCode,
                        ref: $schema->ref,
                    )
                ],
            );
        } elseif (!Generator::isDefault($schema->properties)) {
            $newSchema = new Schema(
                properties: [
                    new Property(
                        property: $layoutDataCode,
                        required: Generator::isDefault($schema->required) ? null : $schema->required,
                        properties: (array)$schema->properties,
                        type: 'object',
                    )
                ],
            );
        } elseif (!Generator::isDefault($schema->items) && $schema->items instanceof Items) {
            $newSchema = new Schema(
                properties: [
                    new Property(
                        property: $layoutDataCode,
                        required: Generator::isDefault($schema->required) ? null : $schema->required,
                        type: 'array',
                        items: $schema->items,
                    )
                ],
            );
        }
        return $newSchema ?: $schema;
    }
}
