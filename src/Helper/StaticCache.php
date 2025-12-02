<?php

namespace WebmanTech\Swagger\Helper;

use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 */
final class StaticCache implements CacheInterface
{
    private static array $cache = [];

    /**
     * @inheritDoc
     */
    public function get($key, $default = null): mixed
    {
        return self::$cache[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        self::$cache[$key] = $value;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        unset(self::$cache[$key]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        self::$cache = [];
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
        return isset(self::$cache[$key]);
    }
}
