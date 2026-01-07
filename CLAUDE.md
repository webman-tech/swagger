# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

[Swagger OpenAPI](https://swagger.io/) 在 webman 中的一键配置启用方案，基于 [zircote/swagger-php](https://github.com/zircote/swagger-php) (v5) 实现。

**核心功能**：
- **零配置启动**：安装后直接访问 `/openapi` 即可看到 Swagger UI
- **多文档支持**：单应用下多个 Swagger 文档
- **动态配置**：动态修改注解下的 Swagger 文档
- **丰富配置**：host 限制、Swagger UI 配置、OpenAPI 配置
- **性能优化**：服务启动后缓存，开发环境自动更新
- **路由自动注册**：支持自动注册 webman 路由
- **跨框架兼容**：不仅仅支持 webman

## 开发命令

测试、静态分析等通用命令与根项目一致，详见根目录 [CLAUDE.md](../../CLAUDE.md)。

## 项目架构

### 核心组件
- **RouteAnnotation**：
  - `Reader`：注解读取器
  - `Processors`：各种处理器
    - `SortComponentsProcessor`：组件排序
    - `AppendResponseProcessor`：追加响应
    - `MergeClassInfoProcessor`：合并类信息
    - `XSchemaRequestProcessor`：X-Schema 请求处理
    - `XSchemaResponseProcessor`：X-Schema 响应处理
    - `ExpandDTOAttributionsProcessor`：DTO 属性扩展
    - `ExpandEnumDescriptionProcessor`：枚举描述扩展
    - `ExpandEloquentModelProcessor`：Eloquent 模型扩展
- **Overwrite**：
  - `Generator`：自定义 Generator
  - `Analysis`：自定义 Analysis
  - `Analysers/AttributeAnnotationFactory`：自定义注解工厂
- **Helper**：
  - `SwaggerHelper`：Swagger 助手
- **Controller**：
  - `OpenapiController`：OpenAPI 控制器
- **DTO**：
  - `SchemaConstants`：Schema 常量
- **Enums**：
  - `PropertyInEnum`：属性位置枚举
- **Middleware**：
  - `HostForbiddenMiddleware`：Host 禁止中间件

### 目录结构
- `src/`：
  - `RouteAnnotation/`：路由注解相关
  - `Overwrite/`：覆盖 swagger-php 的类
  - `Helper/`：助手类
  - `Controller/`：控制器
  - `DTO/`：数据传输对象
  - `Enums/`：枚举类
  - `Middleware/`：中间件
- `copy/`：配置文件模板
- `src/Install.php`：Webman 安装脚本

测试文件位于项目根目录的 `tests/Unit/Swagger/`。

## 代码风格

与根项目保持一致，详见根目录 [CLAUDE.md](../../CLAUDE.md)。

## 注意事项

1. **当前版本**：使用 swagger-php v5 (>=5.2 <5.5)
2. **API 方法**：
   - 使用 `getSchemaForSource()` 获取 Schema
   - 使用 `$analysis->process([new Processor()])` 调用处理器
3. **TypesTrait**：可以使用 `OpenApi\Processors\Concerns\TypesTrait` 进行类型映射
4. **性能优化**：
   - 生产环境使用缓存
   - 开发环境自动更新文档
5. **与 DTO 集成**：
   - `ExpandDTOAttributionsProcessor` 自动从 DTO 的 `ValidationRules` 提取类型信息
   - 支持复杂类型：嵌套数组、关联数组、多维数组等
6. **测试相关**：
   - 修改 DTO 或 Swagger 相关代码后，记得更新对应的 snapshot 文件
   - 使用 `vendor/bin/pest --update-snapshots` 更新快照
   - 单元测试在项目根目录的 `tests/Unit/Swagger/` 下，而非包内
