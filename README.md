# webman-tech/swagger

本项目是从 [webman-tech/components-monorepo](https://github.com/orgs/webman-tech/components-monorepo) 自动 split 出来的，请勿直接修改

## 简介

[Swagger OpenAPI](https://swagger.io/) 在 webman 中的一键配置启用方案，基于 [zircote/swagger-php](https://github.com/zircote/swagger-php) 实现。

该组件提供了一种简便的方式来为 webman 应用生成和展示 API 文档，支持通过注解或属性（Attribute）方式定义 API 接口，并自动生成对应的 OpenAPI 文档和 Swagger UI 界面。

## 功能特性

- **零配置启动**：安装后直接访问 `/openapi` 即可看到 Swagger UI 界面
- **多文档支持**：支持单应用下多个 Swagger 文档（多路由，不同 API 文档）
- **动态配置**：支持动态修改注解下的 Swagger 文档，解决注解下无法写动态配置的问题
- **丰富配置**：支持 host 访问限制、Swagger UI 配置、OpenAPI 配置等
- **性能优化**：服务启动后缓存文档内容，开发环境支持自动更新
- **路由自动注册**：支持自动注册 webman 路由
- **跨框架兼容**：不仅支持 webman 环境，也可在其他环境中使用

## 安装

```bash
composer require webman-tech/swagger
```

## 核心组件

### Swagger 主类

[Swagger](src/Swagger.php) 是主要入口，提供 `registerGlobalRoute()` 注册全局路由和 `registerRoute()` 注册自定义路由两个方法。

### 配置 DTO

#### ConfigRegisterRouteDTO

路由注册配置，控制路由注册行为，包含启用开关（`enable`）、路由前缀（`route_prefix`）、访问权限控制（`host_forbidden`）、Basic 认证（`basic_auth`）、Swagger UI 配置（`swagger_ui`）、OpenAPI 文档配置（`openapi_doc`）、是否注册 webman 路由（`register_route`）及额外中间件（`middlewares`）。

#### ConfigBasicAuthDTO

Basic 认证配置，默认关闭。启用后访问 Swagger UI 和 OpenAPI 文档时需要提供用户名和密码。支持配置用户名（`username`）、密码（`password`）和认证提示域（`realm`）。

#### ConfigOpenapiDocDTO

OpenAPI 文档配置，默认输出版本为 OpenAPI 3.1.0，支持扫描目录（`scan_path`）、排除目录（`scan_exclude`）、文档格式（`format`，yaml/json）、运行时动态修改回调（`modify`）、缓存键（`cache_key`）及 Schema 相关配置。

#### ConfigSwaggerUiDTO

Swagger UI 配置，支持自定义视图（`view`）、视图路径（`view_path`）、静态资源基础 URL（`assets_base_url`）及视图数据（`data`）。

#### ConfigHostForbiddenDTO

主机访问限制配置，支持启用开关（`enable`）、允许内网访问（`ip_white_list_intranet`）、IP 白名单（`ip_white_list`）及主机白名单（`host_white_list`）。默认仅允许内网环境访问。

### OpenapiController

[OpenapiController](src/Controller/OpenapiController.php) 负责处理文档展示，提供 `swaggerUI()` 展示 Swagger UI 界面和 `openapiDoc()` 返回 OpenAPI 文档内容两个动作。

### Reader 路由注解解析

[Reader](src/RouteAnnotation/Reader.php) 用于解析 OpenAPI 注解并生成路由配置。

### SchemaConstants 常量定义

[SchemaConstants](src/DTO/SchemaConstants.php) 定义了用于 Schema 的扩展常量，包括 `X_NAME`（命名路由）、`X_PATH`（路由路径）、`X_MIDDLEWARE`（路由中间件）、`X_SCHEMA_REQUEST`（请求 Schema）、`X_SCHEMA_RESPONSE`（响应 Schema）。

## 参考示例

- [webman 使用最佳实践](https://github.com/krissss/webman-basic/tree/master/app/api)
- [webman 使用 swagger 示例：注解模式的 crud](https://github.com/webman-tech/webman-samples/tree/swagger-attributions)
- [webman 使用 swagger 示例：多 swagger 文档](https://github.com/webman-tech/webman-samples/tree/swagger-multi)

## AI 辅助

- **开发维护**：[AGENTS.md](AGENTS.md) — 面向 AI 的代码结构和开发规范说明
- **使用指南**：[skills/webman-tech-swagger-best-practices/SKILL.md](skills/webman-tech-swagger-best-practices/SKILL.md) — 面向 AI 的最佳实践，可安装到 Claude Code 的 skills 目录使用
