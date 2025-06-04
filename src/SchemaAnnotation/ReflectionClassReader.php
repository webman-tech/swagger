<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use OpenApi\Attributes as OA;
use OpenApi\Generator;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use WebmanTech\Swagger\SchemaAnnotation\DTO\ClassInfoDTO;

/**
 * @link https://swagger.io/docs/specification/v3_0/data-models/data-types/
 */
final class ReflectionClassReader
{
    private array $cache = [];
    private static ?self $instance = null;

    /**
     * @param class-string $class
     * @return ClassInfoDTO
     */
    public static function read(string $class): ClassInfoDTO
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        $self = self::$instance;
        return $self->readByCache($class);
    }

    private function readByCache(string $class): ClassInfoDTO
    {
        if (!isset($this->cache[$class])) {
            $this->cache[$class] = $this->parse($class);
        }
        return $this->cache[$class];
    }

    private function parse($class): ClassInfoDTO
    {
        $classInfo = new ClassInfoDTO();

        $reflectionClass = new ReflectionClass($class);

        $classInfo->required = $this->parseRequired($reflectionClass);
        $classInfo->nullable = $this->parseNullable($reflectionClass);
        $classInfo->propertyTypes = $this->parsePropertyTypes($reflectionClass);

        return $classInfo;
    }

    private function parseRequired(ReflectionClass $reflectionClass): array
    {
        $attributes = $reflectionClass->getAttributes(OA\Schema::class);
        if (count($attributes) <= 0) {
            return [];
        }
        $oaSchema = $attributes[0]->newInstance();
        $required = $oaSchema->required;
        if ($this->isOADefault($required)) {
            $required = [];
        }
        return $required;
    }

    private function parseNullable(ReflectionClass $reflectionClass): array
    {
        $nullable = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(OA\Property::class);
            if (count($attributes) <= 0) {
                continue;
            }
            /** @var OA\Property $oaProperty */
            $oaProperty = $attributes[0]->newInstance();
            if ($property->hasType() && $property->getType()->allowsNull()) {
                $nullable[] = $property->getName();
            }
            if (!$this->isOADefault($oaProperty->nullable) && $oaProperty->nullable) {
                $nullable[] = $property->getName();
            }
        }
        return array_values(array_unique($nullable));
    }

    private function parsePropertyTypes(ReflectionClass $reflectionClass): array
    {
        $propertyTypes = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(OA\Property::class);
            if (count($attributes) <= 0) {
                continue;
            }
            $oaProperty = $attributes[0]->newInstance();
            $types = 'mixed';
            if ($property->hasType()) {
                $types = $this->getTypesFromReflectionType($property->getType());
            }
            if ($types === 'mixed' || $types === 'array') {
                $isArray = $types === 'array';
                $types = $this->getTypesFromOAProperty($oaProperty);
                if ($isArray && $types === 'mixed') {
                    $types = 'array';
                }
            }
            $propertyTypes[$property->getName()] = $types;
        }
        return $propertyTypes;
    }

    private function isOADefault($value): bool
    {
        return Generator::isDefault($value);
    }

    private function getTypesFromReflectionType(\ReflectionType $type): string|array
    {
        if ($type instanceof ReflectionUnionType) {
            return array_map(fn(ReflectionNamedType $type) => $this->getTypesFromReflectionType($type), $type->getTypes());
        }
        if ($type instanceof ReflectionIntersectionType) {
            // 不支持交集形式的参数校验，因此不做类型返回
            return 'mixed';
        }
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return $this->normalizeType($type->getName());
            }
            return 'object_' . $type->getName();
        }
        throw new \InvalidArgumentException(sprintf('Unsupported type "%s"', get_class($type)));
    }

    private function getTypesFromOAProperty(OA\Property $property): string|array
    {
        if (!$this->isOADefault($property->ref)) {
            return 'object_' . $property->ref;
        }
        if (!$this->isOADefault($property->items)) {
            if (!$this->isOADefault($property->items->ref)) {
                return 'array_' . $property->items->ref;
            }
        }

        $type = $property->type;
        if ($this->isOADefault($type)) {
            $type = 'mixed';
        }
        if (is_array($type)) {
            return array_map(fn($item) => $this->normalizeType($item), $type);
        }
        return $this->normalizeType($type);
    }

    private function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }
        return match ($type) {
            'string' => 'string',
            'int', 'integer' => 'integer',
            'number' => 'number',
            'float' => 'float',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
            default => 'mixed',
        };
    }
}