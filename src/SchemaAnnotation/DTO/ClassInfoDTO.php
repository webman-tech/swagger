<?php

namespace WebmanTech\Swagger\SchemaAnnotation\DTO;

use Illuminate\Support\Str;
use WebmanTech\Swagger\DTO\BaseDTO;
use WebmanTech\Swagger\SchemaAnnotation\BaseSchema;
use WebmanTech\Swagger\SchemaAnnotation\ReflectionClassReader;

/**
 * @property array<string, array{required: bool, types: string[]}> $properties
 *
 * @property array $required
 * @property array $nullable
 * @property array<string, string|string[]> $propertyTypes
 */
class ClassInfoDTO extends BaseDTO
{
    protected function initData(): void
    {
        $this->_data = array_merge([
            'required' => [],
            'nullable' => [],
            'propertyTypes' => [],
        ], $this->_data);
    }

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
                if ($data['nullable']) {
                    $rule[] = 'nullable';
                }
                if ($types = $data['types']) {
                    if (is_string($types)) {
                        $rule[] = $this->getValidateType($types);
                    } else {
                        // 多类型校验暂不支持
                    }
                }
                $rules[$property] = array_filter($rule);
            }
            $this->rules = $rules;
        }

        $rules = $this->rules;

        foreach ($extra as $property => $extraRules) {
            if (isset($rules[$property])) {
                if (is_string($extraRules)) {
                    $extraRules = explode('|', $extraRules);
                }
                /** @phpstan-ignore-next-line */
                $rules[$property] = collect($rules[$property])
                    ->merge($extraRules)
                    ->unique(function ($value) {
                        if (!is_string($value)) {
                            return Str::random(); // 非 string 类型的无法去重
                        }
                        return $value;
                    })
                    ->values()
                    ->toArray();
            }
        }

        return $rules;
    }

    public function getNestedPropertyTypes(string $propertyPrefix = ''): array
    {
        $data = [];
        foreach ($this->propertyTypes as $property => $types) {
            $nestedData = [];
            if ($schemaClass = $this->getPropertyObjectSchemaType($property)) {
                $info = ReflectionClassReader::read($schemaClass);
                $nestedData = $info->getNestedPropertyTypes($propertyPrefix . $property . '.');
                $types = 'object'; // 处理完嵌套后将类型改为 object
            } elseif ($schemaClass = $this->getPropertyArrayItemSchemaType($property)) {
                $info = ReflectionClassReader::read($schemaClass);
                $nestedData = $info->getNestedPropertyTypes($propertyPrefix . $property . '.*.');
                $types = 'array'; // 处理完嵌套后将类型改为 object
            }
            $data[$propertyPrefix . $property] = [
                'required' => in_array($property, $this->required),
                'nullable' => in_array($property, $this->nullable),
                'types' => $types,
            ];
            // 将嵌套的属性补充在后面
            if ($nestedData) {
                $data = array_merge($data, $nestedData);
            }
        }
        return $data;
    }

    private function getValidateType(string $type): ?string
    {
        return match ($type) {
            'float', 'number' => 'numeric',
            'object' => 'array',
            'mixed' => null, // mixed 不支持
            default => $type,
        };
    }
}
