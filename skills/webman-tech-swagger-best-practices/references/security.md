# Swagger 安全配置

生产环境需要保护 API 文档，防止未授权访问。

## IP/Host 限制

禁止外网访问文档：

```php
'host_forbidden' => [
    'enable' => true,
    'ip_white_list_intranet' => true,  // 允许所有内网 IP
    'ip_white_list' => ['1.2.3.4'],    // 额外允许的 IP
    'host_white_list' => ['admin.example.com'],  // 允许的 host
],
```

## Basic 认证

需要用户名密码才能访问 Swagger UI：

```php
// config/plugin/webman-tech/swagger/app.php
'basic_auth' => [
    'enable' => true,
    'username' => 'admin',
    'password' => 'your-secure-password',
    'realm' => 'API Documentation',  // 可选，认证提示信息
],
```

也可以在 `registerRoute` 时通过 `global_route` 配置：

```php
'global_route' => [
    'basic_auth' => [
        'enable' => true,
        'username' => 'admin',
        'password' => 'secret',
    ],
],
```

两种安全机制（IP 限制 + Basic Auth）可以同时启用，按顺序执行。

## 自定义中间件

如需更复杂的认证（如基于 auth 包的登录认证），通过 `middlewares` 配置：

```php
'global_route' => [
    'middlewares' => [
        new \WebmanTech\Auth\Middleware\Authentication('admin'),
    ],
],
```

## 推荐配置

- **开发环境**：关闭所有限制，方便调试
- **测试环境**：开启 IP 限制，允许内网访问
- **生产环境**：同时开启 IP 限制 + Basic Auth
