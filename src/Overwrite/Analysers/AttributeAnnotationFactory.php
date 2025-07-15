<?php

namespace WebmanTech\Swagger\Overwrite\Analysers;

use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Context;

class AttributeAnnotationFactory extends \OpenApi\Analysers\AttributeAnnotationFactory
{
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
        if ($this->autoLoadSchemaClasses) {
            if ($reflector instanceof \ReflectionClass) {
                if ($schema = $this->buildClassAnnotation($reflector)) {
                    $annotations[] = $schema;
                }
            } elseif ($reflector instanceof \ReflectionProperty) {
                if ($property = $this->buildPropertyAnnotation($reflector)) {
                    $annotations[] = $property;
                }
            }
        }

        return array_merge(
            $annotations,
            parent::build($reflector, $context),
        );
    }

    private function buildClassAnnotation(\ReflectionClass $reflector): ?Schema
    {
        $attributes = $reflector->getAttributes(AnSchema::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes) {
            return null;
        }
        if (!$this->isSupportClass($reflector->getName())) {
            return null;
        }

        $schema = new Schema();
        $schema->_context = $this->buildContext;
        return $schema;
    }

    private function buildPropertyAnnotation(\ReflectionProperty $reflector): ?Property
    {
        if (!$reflector->isPublic() || $reflector->isStatic()) {
            return null;
        }
        $attributes = $reflector->getAttributes(AnProperty::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes) {
            return null;
        }
        if (!$this->isSupportClass($reflector->getDeclaringClass()->getName())) {
            return null;
        }

        $property = new Property();
        $property->_context = $this->buildContext;
        return $property;
    }

    private function isSupportClass(string $className): bool
    {
        foreach ($this->autoLoadSchemaClasses as $supportClass) {
            if (is_a($className, $supportClass, true)) {
                return true;
            }
        }

        return false;
    }
}
