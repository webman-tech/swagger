<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use OpenApi\Attributes as OA;
use OpenApi\Generator;

class Getter
{
    public function __construct(private \ReflectionClass $reflectionClass)
    {
    }

    private ?OA\Schema $schema = null;

    public function getSchema(): OA\Schema
    {
        if ($this->schema === null) {
            $this->schema = $this->reflectionClass->getAttributes(OA\Schema::class)[0]?->newInstance() ?? new OA\Schema();
        }
        return $this->schema;
    }

    private ?array $properties = null;

    /**
     * @return array<string, OA\Property>
     */
    public function getProperties(): array
    {
        if ($this->properties === null) {
            $this->properties = [];
            foreach ($this->reflectionClass->getProperties() as $property) {
                $this->properties[$property->getName()] = $property->getAttributes(OA\Property::class)[0]?->newInstance() ?? new OA\Property();
            }
        }
        return $this->properties;
    }

    public function getProperty(string $propertyName): OA\Property
    {
        return $this->getProperties()[$propertyName] ?? new OA\Property();
    }

    public function isDefault($value): bool
    {
        return Generator::isDefault($value);
    }

    public function getPropertyType(string $propertyName): ?string
    {
        $type = $this->getProperty($propertyName)->type;
        if ($this->isDefault($type)) {
            return null;
        }
        return $type;
    }

    public function getPropertyObjectRef(string $propertyName): ?string
    {
        $ref = $this->getProperty($propertyName)->ref;
        if ($this->isDefault($ref)) {
            return null;
        }
        return $ref;
    }

    public function getPropertyItemsRef(string $propertyName): ?string
    {
        $items = $this->getProperty($propertyName)->items;
        if ($this->isDefault($items)) {
            return null;
        }
        $ref = $items->ref;
        if ($this->isDefault($ref)) {
            return null;
        }
        return $ref;
    }

    /**"
     * @param string $propertyType "string", "number", "integer", "boolean", "array" or "object"
     * @param $propertyValue
     * @return bool
     */
    public function checkType(string $propertyType, $propertyValue): bool
    {
        return match ($propertyType) {
            'string' => is_string($propertyValue),
            'number' => is_numeric($propertyValue),
            'integer' => is_integer($propertyValue),
            'boolean' => is_bool($propertyValue),
            'array', 'object' => is_array($propertyValue),
            default => false,
        };
    }
}
