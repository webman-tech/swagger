<?php

namespace WebmanTech\Swagger\Helper;

/**
 * @internal
 */
final class ConfigHelper
{
    public static function get(string $key, $default = null)
    {
        return config("plugin.webman-tech.swagger.{$key}", $default);
    }
}
