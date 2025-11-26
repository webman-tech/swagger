<?php

namespace WebmanTech\Swagger\Helper;

use function WebmanTech\CommonUtils\config;

/**
 * @internal
 */
final class ConfigHelper
{
    private static array $testKV = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$testKV[$key] ?? config("plugin.webman-tech.swagger.{$key}", $default);
    }

    public static function getDtoGeneratorPath(): ?string
    {
        return __DIR__ . '/../../../dto/web/index.html';
    }

    /**
     * 测试用
     */
    public static function setForTest(?string $key = null, mixed $value = null): void
    {
        if ($key === null) {
            // reset
            self::$testKV = [];
            return;
        }
        self::$testKV[$key] = $value;
    }
}
