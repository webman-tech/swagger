<?php

namespace WebmanTech\Swagger\Facades;

use WebmanTech\Swagger\Container;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\RouteAnnotation\Reader;
use WebmanTech\Swagger\RouteAnnotation\Register;

/**
 * @method static Reader routeAnnotationReader()
 * @method static Register routeAnnotationRegister()
 */
class Swagger
{
    private static $_container;

    public static function container(): Container
    {
        if (!static::$_container) {
            static::$_container = new Container(ConfigHelper::get('app.components', []));
        }

        return static::$_container;
    }

    public static function __callStatic($name, $arguments)
    {
        $map = [
            'routeAnnotationReader' => Reader::class,
            'routeAnnotationRegister' => Register::class,
        ];
        if (!isset($map[$name])) {
            throw new \RuntimeException('Method ' . $name . ' not exists.');
        }

        return static::container()->get($map[$name]);
    }
}