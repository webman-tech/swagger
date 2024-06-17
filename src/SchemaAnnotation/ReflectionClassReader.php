<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use OpenApi\Attributes as OA;
use OpenApi\Generator;

final class ReflectionClassReader
{
    private array $cache = [];

    /**
     * @param $class
     * @return array<string, array{required: bool, type: string}>
     */
    public function read($class): array
    {
        if (!isset($this->cache[$class])) {
            $this->cache[$class] = $this->parse($class);
        }
        return $this->cache[$class];
    }

    private function parse($class): array
    {
        $reflectionClass = new \ReflectionClass($class);

        $oaSchema = $reflectionClass->getAttributes(OA\Schema::class)[0]?->newInstance() ?? new OA\Schema();
        $required = $oaSchema->required;
        if ($this->isOADefault($required)) {
            $required = [];
        }

        $propertyTypes = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $oaProperty = $property->getAttributes(OA\Property::class)[0]?->newInstance();
            if (!$oaProperty) {
                continue;
            }
            if ($property->hasType()) {
                $type = $property->getType()->getName();
            } else {
                $type = $oaProperty->type;
                if ($this->isOADefault($type)) {
                    $type = null;
                }
            }
            $propertyTypes[$property->getName()] = $type;
        }

        $data = [];
        foreach ($propertyTypes as $property => $type) {
            $data[$property] = [
                'required' => in_array($property, $required),
                'type' => $type,
            ];
        }
        return $data;
    }

    public function isOADefault($value): bool
    {
        return Generator::isDefault($value);
    }

    private function normalizeType(?string $type): string
    {
        match ($type) {
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
            'mixed' => 'mixed',
            default => $type,
        };
    }
}