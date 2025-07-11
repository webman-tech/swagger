<?php

namespace WebmanTech\Swagger\DTO;

/**
 * 用于定义 schema 用到的常量
 */
final class SchemaConstants
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
     * 命名路由的前缀
     */
    public const MIDDLEWARE_NAMED_PREFIX = '@named:';

    /**
     * 将 schema 转到 request 上
     */
    public const X_SCHEMA_REQUEST = 'schema-request';
    /**
     * 将 schema 转到 response 上
     */
    public const X_SCHEMA_RESPONSE = 'schema-response';
    /**
     * @deprecated 使用 X_SCHEMA_REQUEST 代替
     */
    public const X_SCHEMA_TO_PARAMETERS = self::X_SCHEMA_REQUEST;

    /**
     * OA\Property 的 x 中使用的 in 参数名
     */
    public const X_PROPERTY_IN = 'in';
    /**
     * OA\Property 的 x.in 的支持的值
     */
    public const X_PROPERTY_IN_JSON = 'json';
    public const X_PROPERTY_IN_QUERY = 'query';
    public const X_PROPERTY_IN_PATH = 'path';
    public const X_PROPERTY_IN_HEADER = 'header';
    public const X_PROPERTY_IN_COOKIE = 'cookie';
    public const X_PROPERTY_IN_BODY = 'body';
    public const X_PROPERTY_IN_POST = 'post'; // alias -> json
    public const X_PROPERTY_IN_GET = 'get'; // alias -> query
}
