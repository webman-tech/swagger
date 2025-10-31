# webman-tech/swagger

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split
出来的，请勿直接修改

## 简介

[Swagger OpenAPI](https://swagger.io/) 在 webman 中的一键配置启用方案，基于 [zircote/swagger-php](https://github.com/zircote/swagger-php) 实现。

该组件提供了一种简便的方式来为 webman 应用生成和展示 API 文档，支持通过注解或属性(Attribute)方式定义 API 接口，并自动生成对应的 OpenAPI 文档和 Swagger UI 界面。

## 功能特性

- **零配置启动**：安装后直接访问 `/openapi` 即可看到 Swagger UI 界面
- **多文档支持**：支持单应用下多个 Swagger 文档（多路由，不同 API 文档）
- **动态配置**：支持动态修改注解下的 Swagger 文档（解决注解下无法写动态配置的问题）
- **丰富配置**：支持 host 访问限制、Swagger UI 配置、OpenAPI 配置等
- **性能优化**：服务启动后缓存文档内容，开发环境支持自动更新
- **路由自动注册**：支持自动注册 webman 路由
- **跨框架兼容**：不仅仅支持 webman 环境，也可在其他环境中使用（需要调整配置）

## 安装

```bash
composer require webman-tech/swagger
```

## 快速开始

### 零配置使用

安装依赖后直接访问 `/openapi` 即可查看 Swagger 文档，默认会扫描整个 `app_path()` 目录。

### 基本配置

在 `config/plugin/webman-tech/swagger/app.php` 中配置：

```php
return [
    'global_route' => [
        'enable' => true,
        'route_prefix' => '/openapi',
        'openapi_doc' => [
            'scan_path' => [app_path()],
        ],
    ],
];
```

## 核心组件

### Swagger 主类

[Swagger](src/Swagger.php) 类是主要入口，用于注册路由和生成文档：

```php
use WebmanTech\Swagger\Swagger;

// 注册全局路由
Swagger::create()->registerGlobalRoute();

// 注册自定义路由
Swagger::create()->registerRoute([
    'route_prefix' => '/api-doc',
    'openapi_doc' => [
        'scan_path' => [app_path() . '/controller/api'],
    ],
]);
```

### 配置 DTO

#### ConfigRegisterRouteDTO

路由注册配置，用于控制路由注册行为：

- `enable`: 是否启用
- `route_prefix`: OpenAPI 文档的路由前缀
- `host_forbidden`: 访问权限控制配置
- `swagger_ui`: Swagger UI 配置
- `openapi_doc`: OpenAPI 文档配置
- `register_route`: 是否注册 webman 路由

#### ConfigOpenapiDocDTO

OpenAPI 文档配置：

- `scan_path`: 扫描的目录
- `scan_exclude`: 扫描忽略的目录
- `format`: 文档格式（yaml/json）
- `modify`: 修改 OpenAPI 对象的回调函数
- `cache_key`: 缓存用的 key
- `schema_*`: Schema 相关配置

#### ConfigSwaggerUiDTO

Swagger UI 配置：

- `view`: 视图名称
- `view_path`: 视图路径
- `assets_base_url`: 静态资源基础 URL
- `data`: 视图数据

#### ConfigHostForbiddenDTO

主机访问限制配置：

- `enable`: 是否启用
- `ip_white_list_intranet`: 是否允许内网访问
- `ip_white_list`: 允许访问的指定 IP
- `host_white_list`: 允许访问的指定主机

### 控制器

[OpenapiController](src/Controller/OpenapiController.php) 负责处理文档展示和接口：

- `swaggerUI()`: 展示 Swagger UI 界面
- `openapiDoc()`: 返回 OpenAPI 文档内容

### 路由注解解析

[Reader](src/RouteAnnotation/Reader.php) 类用于解析 OpenAPI 注解并生成路由配置：

```php
use WebmanTech\Swagger\RouteAnnotation\Reader;

$reader = new Reader();
$routes = $reader->getData($scanSources);
```

## 使用指南

### 修改全局配置

#### 方法一：通过注解修改

```php
<?php

namespace app\swagger;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'My App')]
#[OA\Server(url: '/api', description: 'localhost')]
class OpenapiSpec
{
}
```

#### 方法二：通过 modify 回调修改（推荐）

```php
'openapi_doc' => [
    'modify' => function(OA\OpenApi $openApi) {
        $openApi->info->title = 'My App';
        $openApi->info->version = '1.0.0';
        
        $openApi->servers = [
            new OA\Server(['url' => '/api', 'description' => 'localhost']),
        ];
    }
]
```

### 访问控制

为了保证接口文档的安全性，默认仅允许内网环境访问。可通过以下方式配置：

```php
'host_forbidden' => [
    'enable' => true,
    'ip_white_list_intranet' => true,
    'ip_white_list' => [],
    'host_white_list' => [],
]
```

### 多应用支持

```php
use Webman\Route;
use WebmanTech\Swagger\Swagger;

Route::group('/api1', function () {
    Swagger::create()->registerRoute([
        'route_prefix' => '/openapi',
        'openapi_doc' => [
            'scan_path' => app_path() . '/controller/api1',
        ],
    ]);
});

Route::group('/api2', function () {
    Swagger::create()->registerRoute([
        'route_prefix' => '/my-doc',
        'openapi_doc' => [
            'scan_path' => app_path() . '/controller/api2',
        ],
    ]);
});
```

### 路由自动注册

在配置中设置 `register_route` 为 true 即可自动注册 webman 路由：

```php
'global_route' => [
    'register_route' => true,
    // ...
]
```

### DTO 与路由绑定

支持将 DTO 与路由绑定并自动校验：

```php
use app\form\TestForm;
use OpenApi\Attributes as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

class IndexController {
     #[OA\Post(
        path: '/xxx',
        summary: '接口说明',
        x: [
            SchemaConstants::X_SCHEMA_REQUEST => TestForm::class . '@doSomething'
        ],
    )]
    public function api(Request $request) {
        return TestForm::fromRequest($request)
            ->doSomething()
            ->toResponse();
    }
}
```

## 常量定义

[SchemaConstants](src/DTO/SchemaConstants.php) 定义了用于 Schema 的常量：

- `X_NAME`: 命名路由
- `X_PATH`: 路由路径
- `X_MIDDLEWARE`: 路由中间件
- `X_SCHEMA_REQUEST`: 请求 Schema
- `X_SCHEMA_RESPONSE`: 响应 Schema

## 参考示例

- [webman 使用最佳实践](https://github.com/krissss/webman-basic/tree/master/app/api)
- [webman 使用 swagger 示例：注解模式的 crud](https://github.com/webman-tech/webman-samples/tree/swagger-attributions)
- [webman 使用 swagger 示例：多 swagger 文档](https://github.com/webman-tech/webman-samples/tree/swagger-multi)