<?php

namespace WebmanTech\Swagger\Helper;

class ConfigHelper
{
    public static function get(string $key, $default = null)
    {
        return config('plugin.webman-tech.swagger.' . $key, $default);
    }
}