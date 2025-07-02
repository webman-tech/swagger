<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Items as AnItems;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Reflection\ReflectionReaderFactory;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * 将 DTO 中的 ValidationRules 信息附加到 Schema 上
 */
class DTOValidationRulesProcessor
{
    public function __invoke(Analysis $analysis): void
    {
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
                $schemaRequired = SwaggerHelper::getValue($schema->required, []);
                foreach ($schema->properties as $property) {
                    $validationRules = $this->getPropertyValidationRules($className, $property);
                    if ($validationRules === null) {
                        continue;
                    }
                    $this->fillPropertyByItsAttributions($property, $validationRules, $schemaRequired);
                }
                $schema->required = $schemaRequired ?: Generator::UNDEFINED;
            }
        }
    }

    private function getPropertyValidationRules(string $className, AnProperty $property): ?ValidationRules
    {
        $propertyName = $property->_context->property;
        if (!$propertyName) {
            $propertyName = SwaggerHelper::getValue($property->property);
        }
        if (!$propertyName) {
            return null;
        }

        return ReflectionReaderFactory::fromClass($className)->getPublicPropertyValidationRules($propertyName);
    }

    private function fillPropertyByItsAttributions(AnProperty $property, ValidationRules $validationRules, array &$schemaRequired): void
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
                if ($validationRules->arrayItem instanceof ValidationRules) {
                    $newProperty = new Property();
                    $newRequired = [];
                    $this->fillPropertyByItsAttributions($newProperty, $validationRules->arrayItem, $newRequired);
                    $itemSchema = SwaggerHelper::renewSchemaWithProperty($newProperty, AnItems::class);
                    $itemSchema->required = $newRequired ?: Generator::UNDEFINED;
                    $property->items = $itemSchema;
                } elseif (is_string($validationRules->arrayItem) && class_exists($validationRules->arrayItem)) {
                    $property->items = new Items(ref: SwaggerHelper::getSchemaRefByClassName($validationRules->arrayItem));
                } else {
                    $property->items = new Items();
                }
            }
        }
        // enum 和 object，Swagger 会自行处理
    }
}
