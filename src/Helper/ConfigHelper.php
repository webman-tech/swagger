<?php

namespace WebmanTech\Swagger\Helper;

/**
 * @internal
 */
final class ConfigHelper
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return config("plugin.webman-tech.swagger.{$key}", $default);
    }
}
