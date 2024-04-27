<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

class BaseDTO implements \JsonSerializable
{
    private $_data;

    public function __construct(array $data = [])
    {
        $this->_data = $data;
    }

    public function __get($name)
    {
        return $this->_data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
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