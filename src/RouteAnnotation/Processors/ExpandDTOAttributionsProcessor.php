<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Items as AnItems;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Components;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
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
            // 仅处理 BaseDTO 的
            if (!$className || !is_a($className, BaseDTO::class, true)) {
                continue;
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
                    // 使用 ValidationRules 补充
                    if ($attribution = $factory->getAttributionValidationRules($propertyName)) {
                        $this->fillPropertyByValidationRules($property, $attribution, $schemaRequired);
                    }
                    // 使用 RequestPropertyIn 补充
                    if ($attribution = $factory->getAttributionRequestPropertyIn($propertyName)) {
                        $this->fillPropertyByRequestPropertyIn($property, $attribution);
                    }
                }
                $schema->required = $schemaRequired ?: Generator::UNDEFINED;
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
            if (Generator::isDefault($property->items)) {
                $schemaItems = null;
                if ($validationRules->arrayItem instanceof ValidationRules) {
                    $newProperty = new Property();
                    $newRequired = [];
                    $this->fillPropertyByValidationRules($newProperty, $validationRules->arrayItem, $newRequired);
                    $schemaItems = SwaggerHelper::renewSchemaWithProperty($newProperty, AnItems::class);
                    $schemaItems->required = $newRequired ?: Generator::UNDEFINED;
                } elseif (is_string($validationRules->arrayItem) && class_exists($validationRules->arrayItem)) {
                    if ($schemaNew = $this->analysis->getSchemaForSource($validationRules->arrayItem)) {
                        $schemaItems = new Items(ref: Components::ref($schemaNew));
                    }
                }
                $property->items = $schemaItems ?? new Items();
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
}
