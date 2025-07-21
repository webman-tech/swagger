<?php

namespace WebmanTech\Swagger\Overwrite\Analysers;

use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Context;
use OpenApi\Processors\Concerns\TypesTrait;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionProperty;

class AttributeAnnotationFactory extends \OpenApi\Analysers\AttributeAnnotationFactory
{
    use TypesTrait;

    private Context $buildContext;

    public function __construct(
        private readonly array $autoLoadSchemaClasses = [],
    )
    {
        parent::__construct();
    }

    public function build(\Reflector $reflector, Context $context): array
    {
        $this->buildContext = $context;

        $annotations = [];
        if ($reflector instanceof ReflectionClass) {
            if ($schema = $this->buildClassAnnotation($reflector)) {
                $annotations[] = $schema;
            }
        } elseif ($reflector instanceof ReflectionProperty) {
            if ($property = $this->buildPropertyAnnotation($reflector)) {
                $annotations[] = $property;
            }
        }

        return array_merge(
            $annotations,
            parent::build($reflector, $context),
        );
    }

    private function buildClassAnnotation(ReflectionClass $reflector): ?Schema
    {
        if ($reflector->getAttributes(AnSchema::class, ReflectionAttribute::IS_INSTANCEOF)) {
            // 主动配置 Schema 的情况
            return null;
        }
        if (!$this->isSupportClass($reflector)) {
            // class 不支持
            return null;
        }

        $schema = new Schema();
        $schema->_context = $this->buildContext;
        // 枚举特殊处理
        if ($reflector->isEnum()) {
            // 设置枚举的类型，否则默认会取 枚举 的键名
            $reflectorEnum = new ReflectionEnum($reflector->getName());
            $this->mapNativeType($schema, $reflectorEnum->getBackingType()?->getName());
        }

        return $schema;
    }

    private function buildPropertyAnnotation(ReflectionProperty $reflector): ?Property
    {
        if (!$reflector->isPublic() || $reflector->isStatic()) {
            // 仅支持 public 非 static 属性
            return null;
        }
        if ($reflector->getAttributes(AnProperty::class, ReflectionAttribute::IS_INSTANCEOF)) {
            // 主动配置 Property 的情况
            return null;
        }
        if (!$this->isSupportClass($reflector->getDeclaringClass())) {
            // class 不支持
            return null;
        }

        $property = new Property();
        $property->_context = $this->buildContext;

        return $property;
    }

    private function isSupportClass(ReflectionClass $reflectionClass): bool
    {
        if ($reflectionClass->isTrait() || $reflectionClass->isEnum()) {
            return true;
        }
        foreach ($this->autoLoadSchemaClasses as $supportClass) {
            if (is_a($reflectionClass->getName(), $supportClass, true)) {
                return true;
            }
        }

        return false;
    }
}
