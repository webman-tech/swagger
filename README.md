# webman-tech/swagger

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split
出来的，请勿直接修改

> 简介

[swagger openapi](https://swagger.io/) 在 webman 中一键配置启用

## 安装

```bash
composer require webman-tech/swagger
```

## 特点

- 基于 [zircote/swagger-php](https://github.com/zircote/swagger-php)（支持 Attribute 模式）
- 支持零配置启动（安装后直接访问 /openapi 即可看到 swagger UI 的界面）
- 支持单应用下多个 swagger 文档（多路由，不同 api 文档）
- 支持动态修改注解下的 swagger 文档（解决注解下无法写动态配置的问题）
- 支持丰富的配置（host 访问限制 / swagger-ui 配置 / openapi 配置）
- 性能优先（服务启动后缓存，开发环境支持自动更新）
- 支持自动注册 webman 路由（已经写了 openapi 文档，再写一遍 webman Route 是不是多此一举？）

## 使用

### 零配置

安装完依赖后打开 `/openapi` 即可访问 swagger 文档

默认扫描整个 `app_path()`

### 修改 @OA\Info 等全局的配置

第一种：通过添加注释的方式修改

```php
<?php

namespace app\swagger;

use OpenApi\Attributes as OA;

/**
 * @link https://swagger.io/specification/#info-object
 */
#[OA\Info(version: '1.0.0', title: 'My App')]
#[OA\Server(url: '/api', description: 'localhost')]
class OpenapiSpec
{
}
```

第二种：通过 modify 的方式修改（建议：因为这种方式支持更加复杂和动态的配置）

以下 `openapi_doc` 支持配置在 全局 和 应用级别（见 [配置说明](#配置说明)）

```php
use OpenApi\Annotations as OA;

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

### 限制访问

为了保证接口文档的安全性，不应该让接口暴露在公网环境下

默认 `host_forbidden` 开启，仅内网环境下可访问，见 [HostForbiddenMiddleware](src/Middleware/HostForbiddenMiddleware.php)

可以通过配置 `app.php` 中的 `host_forbidden` `enable => false` 来全局关闭

### 多应用支持

默认通过配置 `app.php` 中的 `global_route` 配置会启用全局的扫描 `app_path()` 的文档，
可以通过设置 `enable => false` 来关闭

在需要的地方通过 `Swagger::create()->registerRoute()` 来手动注册文档路由

如：

```php
<?php

use Webman\Route;
use WebmanTech\Swagger\Swagger;

Route::group('/api1', function () {
    Swagger::create()->registerRoute([
        'route_prefix' => '/openapi',
        'openapi_doc' => [
            'scan_path' => app_path() . '/controller/api1',
        ],
    ]);

    Route::get('/test', [\app\controller\Test::class, 'index']);
});

Route::group('/api2', function () {
    Swagger::create()->registerRoute([
        'route_prefix' => '/my-doc',
        'openapi_doc' => [
            'scan_path' => app_path() . '/controller/api2',
        ],
    ]);

    Route::get('/test', [\app\controller\Test::class, 'index']);
});
```

如此配置，支持通过 `/api1/openapi` 访问 `api1` 的文档，`/api2/my-doc` 访问 `api2` 的文档

## 配置说明

很多配置都支持全局（多应用共享）、应用级别（仅当前应用生效）

`app.php` 中的配置是全局的

`Swagger::create()->registerRoute($config)` 中的传参 `$config` 是应用级别的

## webman 路由自动注册

在 config 的 `app.php` 中修改 `register_webman_route` 为 true 即可自动注册 webman 路由

## 路由传参与 swagger 文档绑定，并且支持自动校验（仅支持 php>=8.1）

建立一个 Form（可同时用于 POST 和 GET）

```php
<?php

namespace app\form;

use WebmanTech\DTO\Attributes\ValidationRules;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(required: ['name', 'age'])]
class TestForm extends BaseRequestDTO {
    #[OA\Property(description: '名称', example: 'webman')]
    public string $name = '';
    #[OA\Property(description: '年龄', example: 5)]
    #[ValidationRules(max: 100)]
    public int $age = 0;
    #[OA\Property(description: '备注', example: 'xxx')]
    public string $remark = '';
    
    public function doSomething(): TestFormResult {
        return new TestFormResult(
            name: 'abc',
        )
    }
}

#[OA\Schema]
class TestFormResult extends BaseResponseDTO {
    public function __construct(
        #[OA\Property(description: '名称')]
        public string $name,
    ) {
    }
}
```

控制器

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
    public function md(Request $request) {
        return TestForm::fromRequest($request)
            ->doSomething()
            ->toResponse();
    }
}
```

## 参考

[webman 使用 swagger 示例：注解模式的 crud](https://github.com/webman-tech/webman-samples/tree/swagger-attributions)

[webman 使用 swagger 示例：多 swagger 文档](https://github.com/webman-tech/webman-samples/tree/swagger-multi)
