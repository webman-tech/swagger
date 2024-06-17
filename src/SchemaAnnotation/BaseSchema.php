<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use support\Container;

class BaseSchema
{
    private Getter $annotationGetter;
    private Validator $validator;

    final public function __construct()
    {
        /** @var Reader $reader */
        $reader = Container::get(Reader::class);
        $this->annotationGetter = $reader->read(static::class);
        $this->validator = new SchemaValidator($this, $this->annotationGetter);
    }

    /**
     * @var array<string, array{0: string, 1: string}|\TypeError>
     */
    private array $typeErrors = [];

    /**
     * 给属性赋值
     * @param array $data
     * @return $this
     */
    public function load(array $data): static
    {
        // 仅 load 定义了的属性
        $properties = $this->annotationGetter->getProperties();
        foreach ($data as $key => $value) {
            // 非定义属性跳过
            if (!array_key_exists($key, $properties)) {
                continue;
            }
            // 如果是 object 类型的
            if ($class = $this->annotationGetter->getPropertyObjectRef($key)) {
                $value = (new $class)->load($value);
            }
            // 如果是 array 嵌套形式的，且定义了嵌套的 items 类型的
            if ($class = $this->annotationGetter->getPropertyItemsRef($key)) {
                $value = array_map(fn ($item) => (new $class)->load($item), $value);
            }
            // 属性赋值
            try {
                $this->$key = $value;
            } catch (\TypeError $e) {
                $this->validator->addTypeError($key, $e);
            }
        }
        return $this;
    }

    /**
     * 根据定义校验属性值
     * @param array $config
     * @return array
     */
    public function validate(array $config = []): array
    {
        $this->validator->validate($config);
        return $this->validator->getErrors();
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->annotationGetter->getProperties() as $propertyName => $property) {
            $value = $this->$propertyName;
            if (is_array($value)) {
                $value = array_map(fn ($item) => $item instanceof self ? $item->toArray() : $item, $value);
            } else if ($value instanceof self) {
                $value = $value->toArray();
            }
            $result[$propertyName] = $value;
        }
        return $result;
    }
}
