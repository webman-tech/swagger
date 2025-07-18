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
     */
    public const X_MIDDLEWARE = 'route-middleware';

    /**
     * 将 schema 转到 request 上
     */
    public const X_SCHEMA_REQUEST = 'schema-request';
    /**
     * 将 schema 转到 response 上
     */
    public const X_SCHEMA_RESPONSE = 'schema-response';

    /**
     * OA\Property 的 x 中使用的 in 参数名
     * 支持的值见
     * @see RequestPropertyInEnum
     */
    public const X_PROPERTY_IN = 'in';
}
