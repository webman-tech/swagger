<?php

namespace WebmanTech\Swagger\DTO;

/**
 * 用于定义 schema 用到的常量
 */
class SchemaConstants
{
    /**
     * 命名路由
     */
    public const X_NAME = 'route-name';
    /**
     * 路由 path
     * 当 openapi 上的 path 不能满足时路由定义时使用
     * 比如 /user/{id:\d+} 或 /user[/{name}]，可以通过此设置
     */
    public const X_PATH = 'route-path';
    /**
     * 路由中间件
     * @see self::getRouteMiddlewares()
     */
    public const X_MIDDLEWARE = 'route-middleware';
    /**
     * schema 转 parameters
     */
    public const X_SCHEMA_TO_PARAMETERS = 'schema-to-parameters';

    /**
     * 命名路由的前缀
     */
    public const MIDDLEWARE_NAMED_PREFIX = '@named:';
}
