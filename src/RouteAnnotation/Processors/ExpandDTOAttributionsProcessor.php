<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Items as AnItems;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Components;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use ReflectionProperty;
use WebmanTech\DTO\Attributes\RequestPropertyIn;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Enums\PropertyInEnum;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * 将 DTO 中的注解信息附加到 Schema 上
 */
final class ExpandDTOAttributionsProcessor
{
    private Analysis $analysis;

    public function __invoke(Analysis $analysis): void
    {
        $this->analysis = $analysis;

        /** @var AnSchema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(AnSchema::class);
        // 仅处理 Schema 即可，子类（比如 Property 等）不需要
        $schemas = array_filter($schemas, fn(AnSchema $schema) => in_array($schema::class, [AnSchema::class, Schema::class]));

        foreach ($schemas as $schema) {
            $className = SwaggerHelper::getAnnotationClassName($schema);
            if (!$className) {
                continue;
            }
            // 检查是否需要支持
            if ($schema->_context->is('trait') && $schema->_context->filename) {
                // trait 通过内容来鉴别
                $content = file_get_contents($schema->_context->filename) ?: '';
                if (
                    !str_contains($content, ValidationRules::class)
                    && !str_contains($content, RequestPropertyIn::class)
                ) {
                    continue;
                }
            } else {
                // 仅处理 BaseDTO 的
                if (!is_a($className, BaseDTO::class, true)) {
                    continue;
                }
            }
            if (!Generator::isDefault($schema->properties)) {
                $factory = ReflectionReaderFactory::fromClass($className);
                $schemaRequired = SwaggerHelper::getValue($schema->required, []);
                foreach ($schema->properties as $property) {
                    $propertyName = $property->_context->property;
                    if (!$propertyName) {
                        $propertyName = SwaggerHelper::getValue($property->property);
                    }
                    if (!$propertyName) {
                        continue;
                    }
                    // 修复类型
                    $this->fixType($property);
                    // 使用 ValidationRules 补充
                    if ($attribution = $factory->getAttributionValidationRules($propertyName)) {
                        $this->fillPropertyByValidationRules($property, $attribution, $schemaRequired);
                    }
                    // 使用 RequestPropertyIn 补充
                    if ($attribution = $factory->getAttributionRequestPropertyIn($propertyName)) {
                        $this->fillPropertyByRequestPropertyIn($property, $attribution);
                    }
                    // 填充默认值
                    if ($reflection = $factory->getPropertyReflection($propertyName)) {
                        $this->fillDefault($property, $reflection);
                    }
                }
                SwaggerHelper::setValue($schema->required, $schemaRequired);
            }
        }
    }

    private function fixType(AnProperty $property): void
    {
        if (!Generator::isDefault($property->type)) {
            return;
        }
        // 自定义类型
        $types = SwaggerHelper::getAnnotationXValue($property, SchemaConstants::X_PROPERTY_TYPES, array_filter([
            // 默认先取 swagger-php 已经取到的类型
            $property->_context->type,
        ]));
        if ($types) {
            $this->fixUploadedFileType($property, $types);
            return;
        }
        // 没有类型定义的情况
        // 1. 本身代码就没定义类型
        // 2. swagger-php 不能解析联合类型（此处做支持）
        $types = [];
        if (!$property->_context->type && $property->_context->property) {
            $reflectPropertyType = (new ReflectionProperty(SwaggerHelper::getAnnotationClassName($property), $property->_context->property))
                ->getType();
            if ($reflectPropertyType instanceof \ReflectionUnionType) {
                foreach ($reflectPropertyType->getTypes() as $itemType) {
                    if ($itemType instanceof \ReflectionNamedType) {
                        $types[] = $itemType->getName();
                    }
                }
            }
        }
        if (!$types) {
            return;
        }
        // 将联合类型的信息填充到 x-types 上
        $this->fixUploadedFileType($property, $types);
        SwaggerHelper::setAnnotationXValue($property, SchemaConstants::X_PROPERTY_TYPES, $types);
    }

    private function fixUploadedFileType(AnProperty $property, array $types): void
    {
        foreach ($types as $type) {
            if (SwaggerHelper::isTypeUploadedFile($type)) {
                // 有上传类型的情况，将类型改为 binary
                $property->type = 'string';
                $property->format = 'binary';
                return;
            }
        }
    }

    private function fillPropertyByValidationRules(AnProperty $property, ValidationRules $validationRules, array &$schemaRequired): void
    {
        if (Generator::isDefault($property->type)) {
            $property->type = match (true) {
                $validationRules->integer => 'integer',
                $validationRules->numeric => 'number',
                $validationRules->boolean => 'boolean',
                $validationRules->string => 'string',
                $validationRules->object !== null => 'object',
                $validationRules->array => 'array',
                default => Generator::UNDEFINED,
            };
        }
        if ($property->type === 'array' && $validationRules->object !== null) {
            // php 定义是数组，但是实际可能是 object 的情况
            $property->type = 'object';
        }
        if (Generator::isDefault($property->minimum) && $validationRules->min) {
            $property->minimum = $validationRules->min;
        }
        if (Generator::isDefault($property->maximum) && $validationRules->max) {
            $property->maximum = $validationRules->max;
        }
        if (Generator::isDefault($property->minLength) && $validationRules->minLength) {
            $property->minLength = $validationRules->minLength;
        }
        if (Generator::isDefault($property->maxLength) && $validationRules->maxLength) {
            $property->maxLength = $validationRules->maxLength;
        }
        if ($validationRules->required) {
            $schemaRequired[] = $property->property;
        }
        if (Generator::isDefault($property->nullable) && $validationRules->nullable) {
            $property->nullable = true;
        }
        if ($property->type === 'array') {
            // array 类型时，必须 Items
            if (Generator::isDefault($property->items) || Generator::isDefault($property->items->type)) {
                $schemaItems = null;
                if ($validationRules->arrayItem instanceof ValidationRules) {
                    if ($validationRules->arrayItem->object !== null) {
                        $property->type = 'object';
                    } else {
                        $newProperty = new Property();
                        $newRequired = [];
                        $this->fillPropertyByValidationRules($newProperty, $validationRules->arrayItem, $newRequired);
                        $schemaItems = SwaggerHelper::renewSchemaWithProperty($newProperty, AnItems::class);
                        SwaggerHelper::setValue($schemaItems->required, $newRequired);
                    }
                } elseif (is_string($validationRules->arrayItem) && class_exists($validationRules->arrayItem)) {
                    if ($schemaNew = $this->analysis->getSchemaForSource($validationRules->arrayItem)) {
                        $schemaItems = new Items(ref: Components::ref($schemaNew));
                    }
                }
                if ($property->type === 'array') {
                    $property->items = $schemaItems ?? new Items();
                }
            }
        }
        if ($property->type === 'object' && $validationRules->arrayItem && Generator::isDefault($property->additionalProperties)) {
            // 定义为对象
            if ($validationRules->arrayItem instanceof ValidationRules) {
                // arrayItem 定义为简单的 ValidationRules 的情况
                $type = match (true) {
                    $validationRules->arrayItem->integer => 'integer',
                    $validationRules->arrayItem->numeric => 'number',
                    $validationRules->arrayItem->boolean => 'boolean',
                    $validationRules->arrayItem->string => 'string',
                    default => null, // 其他类型的不在此处处理，建议使用标准的类的形式定义
                };
                if ($type) {
                    $property->additionalProperties = new AdditionalProperties(
                        type: $type,
                        nullable: $validationRules->nullable,
                    );
                }
            } elseif (is_string($validationRules->arrayItem) && class_exists($validationRules->arrayItem)) {
                if ($schemaNew = $this->analysis->getSchemaForSource($validationRules->arrayItem)) {
                    $property->additionalProperties = new AdditionalProperties(
                        ref: Components::ref($schemaNew),
                        nullable: $validationRules->nullable,
                    );
                }
            }

        }
        // enum 和 object，Swagger 会自行处理
    }

    private function fillPropertyByRequestPropertyIn(AnProperty $property, RequestPropertyIn $requestPropertyIn): void
    {
        if ($requestPropertyIn->name) {
            $property->property = $requestPropertyIn->name;
        }
        $xPropertyInValue = PropertyInEnum::tryFromDTORequestPropertyIn($requestPropertyIn->getInEnum())->value;
        SwaggerHelper::setAnnotationXValue($property, SchemaConstants::X_PROPERTY_IN, $xPropertyInValue);
    }

    private function fillDefault(AnProperty $property, ReflectionProperty $reflection): void
    {
        if (!Generator::isDefault($property->default)) {
            return;
        }
        SwaggerHelper::setValue($property->default, $reflection->getDefaultValue());
    }
}
