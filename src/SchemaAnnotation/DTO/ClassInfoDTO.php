<?php

namespace WebmanTech\Swagger\SchemaAnnotation\DTO;

use WebmanTech\Swagger\DTO\BaseDTO;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;
use WebmanTech\Swagger\SchemaAnnotation\ReflectionClassReader;

/**
 * @property array<string, array{required: bool, types: string[]}> $properties
 *
 * @property array $required
 * @property array<string, string|string[]> $propertyTypes
 */
class ClassInfoDTO extends BaseDTO
{
    public function getPropertyNames(): array
    {
        return array_keys($this->propertyTypes);
    }

    public function isPropertyExist(string $property): bool
    {
        return array_key_exists($property, $this->propertyTypes);
    }

    /**
     * @param string $property
     * @return class-string<BaseSchema>|null
     */
    public function getPropertyObjectSchemaType(string $property): ?string
    {
        if (!isset($this->propertyTypes[$property])) {
            return null;
        }
        $types = $this->propertyTypes[$property];
        if (is_string($types) && str_starts_with($types, 'object_')) {
            return substr($types, 7);
        }
        return null;
    }

    /**
     * @param string $property
     * @return class-string<BaseSchema>|null
     */
    public function getPropertyArrayItemSchemaType(string $property): ?string
    {
        if (!isset($this->propertyTypes[$property])) {
            return null;
        }
        $types = $this->propertyTypes[$property];
        if (is_string($types) && str_starts_with($types, 'array_')) {
            return substr($types, 6);
        }
        return null;
    }

    private ?array $rules = null;

    public function getLaravelValidationRules(array $extra = []): array
    {
        if ($this->rules === null) {
            $rules = [];
            foreach ($this->getNestedPropertyTypes() as $property => $data) {
                $rule = [];
                if ($data['required']) {
                    $rule[] = 'required';
                }
                if ($data['types']) {
                    foreach ($data['types'] as $type) {
                        $rule[] = $this->getValidateType($type);
                    }
                }
                if (isset($extra[$property])) {
                    $rule = array_merge($rule, $extra[$property]);
                }
                $rules[$property] = $rule;
            }
            $this->rules = $rules;
        }

        $rules = $this->rules;

        foreach ($extra as $property => $extraRules) {
            if (isset($rules[$property])) {
                $rules[$property] = array_merge($rules[$property], is_array($extraRules) ? $extraRules : [$extraRules]);
            }
        }

        return $rules;
    }

    private function getNestedPropertyTypes(): array
    {
        $data = [];
        foreach ($this->propertyTypes as $property => $types) {
            $nestedData = [];
            if ($schemaClass = $this->getPropertyObjectSchemaType($property)) {
                $info = ReflectionClassReader::read($schemaClass);
                // TODO 多级嵌套
                foreach ($info->propertyTypes as $nestedProperty => $nestedTypes) {
                    $nestedData[$property . '.' . $nestedProperty] = [
                        'required' => in_array($nestedProperty, $info->required),
                        'types' => is_array($nestedTypes) ? $nestedTypes : [$nestedTypes],
                    ];
                }

                $types = 'object';
            } elseif ($schemaClass = $this->getPropertyArrayItemSchemaType($property)) {
                $info = ReflectionClassReader::read($schemaClass);
                foreach ($info->propertyTypes as $nestedProperty => $nestedTypes) {
                    $nestedData[$property . '.*.' . $nestedProperty] = [
                        'required' => in_array($nestedProperty, $info->required),
                        'types' => is_array($nestedTypes) ? $nestedTypes : [$nestedTypes],
                    ];
                }
                $types = 'array';
            }
            $data[$property] = [
                'required' => in_array($property, $this->required),
                'types' => is_array($types) ? $types : [$types],
            ];

            if ($nestedData) {
                $data = array_merge($data, $nestedData);
            }
        }
        return $data;
    }

    private function getValidateType(string $type): string
    {
        return match ($type) {
            'float', 'number' => 'numeric',
            'object' => 'array',
            default => $type,
        };
    }
}