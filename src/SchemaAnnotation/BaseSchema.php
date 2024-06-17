<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use WebmanTech\Swagger\SchemaAnnotation\DTO\ClassInfoDTO;

abstract class BaseSchema
{
    private ClassInfoDTO $classInfo;

    final public function __construct()
    {
        $this->classInfo = ReflectionClassReader::read(static::class);
    }

    protected function validationExtraRules(): array
    {
        return [];
    }

    protected function validationMessages(): array
    {
        return [];
    }

    protected function validationCustomAttributes(): array
    {
        return [];
    }

    /**
     * 装载数据
     * @param array $data
     * @param null|ValidatorFactory $validator 提供验证器后会先执行验证
     * @return $this
     * @throws ValidationException
     */
    public function load(array $data, $validator = null): static
    {
        if ($validator instanceof ValidatorFactory) {
            $validator = $validator->make(
                $data,
                $this->classInfo->getLaravelValidationRules($this->validationExtraRules()),
                $this->validationMessages(),
                $this->validationCustomAttributes(),
            );
            $data = $validator->validated();
        }

        foreach ($data as $property => $value) {
            // 属性不存在，跳过
            if (!$this->classInfo->isPropertyExist($property)) {
                continue;
            }
            // 如果是 object 类型的
            if ($schemaClass = $this->classInfo->getPropertyObjectSchemaType($property)) {
                $value = (new $schemaClass)->load($value, $validator);
            }
            // 如果是 array 嵌套形式的
            if ($schemaClass = $this->classInfo->getPropertyArrayItemSchemaType($property)) {
                $value = array_map(fn($item) => (new $schemaClass)->load($item, $validator), $value);
            }
            // 属性赋值
            try {
                $this->$property = $value;
            } catch (\TypeError $e) {
                // 类型错误忽略
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->classInfo->getPropertyNames() as $property) {
            $value = $this->$property;
            if (is_array($value)) {
                $value = array_map(fn($item) => $item instanceof self ? $item->toArray() : $item, $value);
            } else if ($value instanceof self) {
                $value = $value->toArray();
            }
            $result[$property] = $value;
        }
        return $result;
    }
}
