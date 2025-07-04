<?php

namespace WebmanTech\Swagger\Helper;

use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Generator;

/**
 * @internal
 */
final class SwaggerHelper
{
    /**
     * 获取值
     */
    public static function getValue($value, $default = null)
    {
        return Generator::isDefault($value) ? $default : $value;
    }

    /**
     * 获取 注解 的 class 的名称
     */
    public static function getAnnotationClassName(AbstractAnnotation $annotation): ?string
    {
        if ($annotation->_context->is('class')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->class);
        } elseif ($annotation->_context->is('interface')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->interface);
        } elseif ($annotation->_context->is('trait')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->trait);
        } elseif ($annotation->_context->is('enum')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->enum);
        } else {
            $className = $annotation->_context->fullyQualifiedName(
                $annotation->_context->class
                ?? $annotation->_context->interface
                ?? $annotation->_context->trait
                ?? $annotation->_context->enum
            );
        }

        return $className;
    }

    /**
     * 根据 className 和 propertyName 获取 property 的 ref
     */
//    public static function getPropertyRefByClassNameAndPropertyName(Analysis $analysis, string|AnSchema $schema, string $propertyName): ?string
//    {
//        if (is_string($schema)) {
//            $schema = $analysis->getSchemaForSource($schema);
//            if (!$schema) {
//                return null;
//            }
//        }
//
//        $fnGetSchemaName = function (AnSchema $schema) {
//            if (Generator::isDefault($schema->schema)) {
//                return self::className2schemaName(self::getAnnotationClassName($schema));
//            }
//            return $schema->schema;
//        };
//
//        $fnFindPropertyInSchema = function (AnSchema $schema, string $propertyName, ?string $prefix = null) use ($fnGetSchemaName, &$fnFindPropertyInSchema, $analysis) {
//            if (!Generator::isDefault($schema->properties)) {
//                $traits = [];
//                $hasTraitAllOf = false;
//                foreach ($schema->properties as $property) {
//                    $thisPropertyIsInTrait = false;
//                    if ($property->_context->trait) {
//                        $hasTraitAllOf = true;
//                        $thisPropertyIsInTrait = true;
//                        $key = $property->_context->namespace . $property->_context->trait;
//                        $traits[$key] = 1;
//                    }
//                    if ($property->property === $propertyName) {
//                        $name = $prefix;
//                        if ($name === null) {
//                            if ($thisPropertyIsInTrait) {
//                                $name = $fnGetSchemaName($property);
//                            } else {
//                                $name = $fnGetSchemaName($schema);
//                            }
//                        }
//                        if (!$thisPropertyIsInTrait && $hasTraitAllOf) {
//                            $name .= '/allOf/[' . count($traits) . ']';
//                        }
//                        return $name . '/properties/' . $property->property;
//                    }
//                }
//            }
//            if (!Generator::isDefault($schema->ref)) {
//                $refSchema = self::getSchemaBySchemaRef($schema->ref, $analysis);
//                if ($refSchema) {
//                    $name = $fnFindPropertyInSchema($refSchema, $propertyName);
//                    if ($name) {
//                        return $name;
//                    }
//                }
//            }
//            if (!Generator::isDefault($schema->allOf)) {
//                foreach ($schema->allOf as $index => $item) {
//                    $name = $fnFindPropertyInSchema($item, $propertyName, $fnGetSchemaName($schema) . "/allOf/[$index]");
//                    if ($name) {
//                        return $name;
//                    }
//                }
//            }
//
//            return '';
//        };
//
//        $name = $fnFindPropertyInSchema($schema, $propertyName);
//        if (!$name) {
//            return null;
//        }
//
//        return Components::SCHEMA_REF . $name;
//    }

    /**
     * 获取 property 的 x 属性
     */
    public static function getPropertyXValue(AnProperty $property, string $key): mixed
    {
        $value = null;
        if (!Generator::isDefault($property->x) && array_key_exists($key, $property->x)) {
            $value = $property->x[$key];
        }
        return $value;
    }

    /**
     * 设置 property 的 x 属性
     */
    public static function setPropertyXValue(AnProperty $property, string $key, $value): void
    {
        if (Generator::isDefault($property->x)) {
            $property->x = [];
        }
        $property->x[$key] = $value;
    }

    /**
     * 通过 property 构造一个 Schema
     * @param class-string<AnSchema> $schemaClass
     */
    public static function renewSchemaWithProperty(AnProperty $property, string $schemaClass = AnSchema::class): AnSchema
    {
        return new $schemaClass(array_filter([
            'description' => SwaggerHelper::getValue($property->description),
            'type' => SwaggerHelper::getValue($property->type),
            'format' => SwaggerHelper::getValue($property->format),
            'items' => SwaggerHelper::getValue($property->items),
            'default' => SwaggerHelper::getValue($property->default),
            'maximum' => SwaggerHelper::getValue($property->maximum),
            'minimum' => SwaggerHelper::getValue($property->minimum),
            'maxLength' => SwaggerHelper::getValue($property->maxLength),
            'minLength' => SwaggerHelper::getValue($property->minLength),
            'pattern' => SwaggerHelper::getValue($property->pattern),
            'enum' => SwaggerHelper::getValue($property->enum),
            'example' => SwaggerHelper::getValue($property->example),
            'nullable' => SwaggerHelper::getValue($property->nullable),
            'additionalProperties' => SwaggerHelper::getValue($property->additionalProperties),
        ], fn($item) => $item !== null));
    }
}
