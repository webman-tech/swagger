<?php

namespace WebmanTech\Swagger\Helper;

use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 */
final class NoneCache implements CacheInterface
{
    /**
     * @inheritDoc
     */
    public function get($key, $default = null): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null): iterable
    {
        throw new \InvalidArgumentException('Not implemented');
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        throw new \InvalidArgumentException('Not implemented');
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        throw new \InvalidArgumentException('Not implemented');
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        return false;
    }
}
