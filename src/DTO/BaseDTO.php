<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\Swagger\Helper\ArrayHelper;

class BaseDTO implements \JsonSerializable
{
    protected $_data;

    final public function __construct(array $data = [])
    {
        $this->_data = $data;
        $this->initData();
    }

    protected function initData()
    {
    }

    public function __get($name)
    {
        return $this->_data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function merge(...$data)
    {
        $toMerge = [];
        foreach ($data as $items) {
            if (!is_array($items) || $items === []) {
                continue;
            }
            $toMerge[] = $items;
        }
        if (!$toMerge) {
            return;
        }

        $this->_data = ArrayHelper::merge(
            $this->toArray(),
            ...$toMerge
        );
        $this->initData();
    }

    public function toArray(): array
    {
        return $this->_data;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}