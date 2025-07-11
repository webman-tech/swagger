<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\Swagger\Helper\ArrayHelper;

/**
 * @internal
 */
class BaseDTO extends \WebmanTech\DTO\BaseDTO implements \JsonSerializable
{
    final public function __construct(protected array $_data = [])
    {
        $this->initData();
    }

    protected function initData(): void
    {
    }

    public function __get(string $name): mixed
    {
        return $this->_data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->_data[$name] = $value;
    }

    /**
     * @param mixed ...$data
     * @return void
     */
    public function merge(...$data): void
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
