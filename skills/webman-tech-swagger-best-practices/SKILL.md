---
name: webman-tech-swagger-best-practices
description: webman-tech/swagger 最佳实践。使用场景：用户为 webman 接口编写 OpenAPI 文档时，给出明确的推荐写法。
---

# webman-tech/swagger 最佳实践

## 核心原则

1. **DTO 即文档**：用 DTO 类定义请求/响应结构，swagger 自动从中提取类型和验证规则
2. **`@handle` 一行搞定请求+响应**：用 `FormClass@handle` 同时声明请求 schema 和响应 schema
3. **Tag 放在 Controller 类上**，不要每个方法都写

DTO 的写法详见 `webman-tech-dto-best-practices` skill。

---

## 快速开始

### 推荐：集中管理注册逻辑

将 swagger 注册逻辑封装到一个专用类，而不是分散在配置文件里：

```php
// app/components/SwaggerRegister.php
use OpenApi\Annotations as OAA;
use OpenApi\Attributes as OA;
use Symfony\Component\Finder\Finder;
use WebmanTech\Swagger\Swagger;

final class SwaggerRegister
{
    public static function register(): void
    {
        Swagger::create()->registerRoute([
            'register_route' => true,
            'swagger_ui' => [
                'tag_sort' => ['认证', '用户', '订单'],  // 控制 Tag 在 UI 中的排序
            ],
            'openapi_doc' => [
                'scan_path' => fn() => [
                    Finder::create()->files()->name('*.php')
                        ->in(app_path('api/controller'))
                        ->exclude(['example']),  // 排除示例目录
                ],
                // 用 modify 动态设置 API 信息，比静态注解更灵活
                'modify' => function (OAA\OpenApi $openapi) {
                    $openapi->info->title = config('app.name') . ' API';
                    $openapi->info->version = '1.0.0';
                    $openapi->servers = [
                        new OA\Server(
                            url: route_url('/api'),
                            description: request()->host(),
                        ),
                    ];
                    // 配置认证方式
                    if (!$openapi->components instanceof OAA\Components) {
                        $openapi->components = new OAA\Components([]);
                    }
                    $openapi->components->securitySchemes = [
                        new OA\SecurityScheme(
                            securityScheme: 'api_key',
                            type: 'apiKey',
                            name: 'X-Api-Key',
                            in: 'header',
                        ),
                    ];
                    $openapi->security = [['api_key' => []]];
                },
                'host_forbidden' => [
                    'ip_white_list_intranet' => true,
                ],
            ],
        ]);
    }
}
```

```php
// config/route.php 或启动文件
SwaggerRegister::register();
```

`modify` 闭包在每次生成文档时执行，可以读取运行时配置（`config()`、`request()->host()` 等），比静态 `#[OA\Info]` 注解更灵活。

---

## 标准接口写法

### 推荐：`@handle` 模式（一行同时声明请求和响应）

```php
use OpenApi\Attributes as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

#[OA\Tag(name: 'users', description: '用户管理')]  // Tag 放类上，不放方法上
final class UserController
{
    #[OA\Post(
        path: '/users',
        summary: '创建用户',
        x: [SchemaConstants::X_SCHEMA_REQUEST => UserCreateForm::class . '@handle']
        //                                                                  ^^^^^^^ 自动从 handle() 返回类型推断响应 schema
    )]
    public function create()
    {
        return UserCreateForm::fromRequest()->handle()->toResponse();
    }
}
```

`@handle` 的工作原理：swagger 读取 `handle()` 方法的返回类型，自动设置为 `x-schema-response`，无需手动声明。

### DTO 类：让类型声明做文档

```php
use OpenApi\Attributes as OA;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\DTO\BaseResponseDTO;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\DTO\Attributes\ValidationRules;

// 请求 DTO — 不需要 #[OA\Schema]，BaseDTO 子类自动被识别
final class UserCreateForm extends BaseRequestDTO
{
    /**
     * 用户姓名
     * @example 张三
     */
    public string $name;

    /**
     * 年龄
     * @example 18
     */
    #[ValidationRules(min: 1, max: 120)]
    public int $age;

    /**
     * @var UserCreateFormAddressItem[]
     */
    public array|null $address = null;

    public function handle(): UserCreateFormResult
    {
        // 业务逻辑
        return new UserCreateFormResult(id: 1);
    }
}

// 响应 DTO — 用构造函数属性提升
final class UserCreateFormResult extends BaseResponseDTO
{
    public function __construct(
        /** @example 1 */
        public readonly int $id,
    ) {}
}

// 嵌套 DTO
final class UserCreateFormAddressItem extends BaseDTO
{
    /** @example 北京 */
    public string $city;
    /** @example 朝阳区 */
    public string $street;
}
```

---

## 文档注释规范

### `@example` 提供示例值

```php
/**
 * 用户状态
 * @example active
 */
public string $status;

/**
 * 年龄
 * @example 18
 */
public int $age;

/**
 * 标签列表
 * @var string[]
 * @example ["tag1", "tag2"]
 */
public array $tags = [];
```

### 属性描述写在 docblock 第一行

```php
/**
 * 订单编号（不是 @param，直接写描述）
 * @example ORD20240101001
 */
public string $orderNo;
```

---

## 多个请求 Schema

当一个接口需要合并多个 schema 时：

```php
#[OA\Post(
    path: '/orders',
    x: [
        SchemaConstants::X_SCHEMA_REQUEST => [
            CreateOrderForm::class,
            CommonPaginationForm::class,  // 合并分页参数
        ]
    ]
)]
```

---

## 响应包装结构（response_layout）

如果所有接口都有统一的响应结构（如 `{code, message, data}`），配置 `response_layout_class`：

```php
// 定义响应结构
#[OA\Schema]
final class ApiResponse
{
    /** @example 0 */
    public int $code = 0;
    /** @example success */
    public string $message = 'success';
    // data 字段由 response_layout_data_code 指定，自动填充
}

// 配置
'openapi_doc' => [
    'response_layout_class' => ApiResponse::class,
    'response_layout_data_code' => 'data',  // 默认就是 'data'
],
```

配置后，所有接口的 200 响应自动包裹在 `ApiResponse` 结构中，`data` 字段填充实际响应 schema。

单个接口可以用 `x-response-layout: null` 跳过包装：

```php
#[OA\Get(
    path: '/health',
    x: [SchemaConstants::X_RESPONSE_LAYOUT => null]  // 不包装
)]
```

---

## 多文档（多个 Swagger 实例）

```php
// 注册第二个文档
Swagger::create()->registerRoute([
    'route_prefix' => '/openapi/admin',
    'openapi_doc' => [
        'scan_path' => [app_path('admin')],
        'cache_key' => 'swagger:admin',  // 必须指定不同的 cache_key
    ],
]);
```

---

## 枚举类型

枚举自动提取描述，只需定义枚举类：

```php
enum StatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function description(): string  // 默认调用此方法
    {
        return match($this) {
            self::Active => '启用',
            self::Inactive => '禁用',
        };
    }
}
```

如果方法名不是 `description`，通过配置指定：

```php
'openapi_doc' => [
    'schema_enum_description_method' => 'label',  // 改为调用 label() 方法
],
```

---

## 路由注解（register_route: true 时）

开启 `register_route` 后，swagger 注解同时作为路由定义，无需在 route.php 中重复写：

```php
#[OA\Post(
    path: '/users',
    summary: '创建用户',
    x: [
        SchemaConstants::X_SCHEMA_REQUEST => UserCreateForm::class . '@handle',
        SchemaConstants::X_NAME => 'user.create',           // 命名路由
        SchemaConstants::X_MIDDLEWARE => [AuthMiddleware::class],  // 中间件
        // SchemaConstants::X_PATH => '/users/{id:\d+}',   // 路由 path 与 openapi path 不同时使用
    ]
)]
```

---

## 安全配置

生产环境禁止外网访问文档：

```php
'host_forbidden' => [
    'enable' => true,
    'ip_white_list_intranet' => true,  // 允许所有内网 IP
    'ip_white_list' => ['1.2.3.4'],    // 额外允许的 IP
    'host_white_list' => ['admin.example.com'],  // 允许的 host
],
```

---

## 常见错误

| 错误 | 原因 | 解决 |
|------|------|------|
| 文档为空 | 缺少 `#[OA\Info]` 或 `modify` 未设置 info | 用 `modify` 闭包设置 `$openapi->info->title` 和 `version` |
| Schema 找不到 | DTO 类不在扫描路径内 | 检查 `scan_path` 配置 |
| `@handle` 不自动推断响应 | `handle()` 没有返回类型声明 | 给 `handle()` 加返回类型 `UserCreateFormResult` |
| 多实例文档互相覆盖 | 未指定不同 `cache_key` | 每个实例配置唯一的 `cache_key` |
| 枚举描述不显示 | 方法名不匹配 | 配置 `schema_enum_description_method` |
