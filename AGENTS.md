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

测试、静态分析等通用命令与根项目一致，详见根目录 [AGENTS.md](../../AGENTS.md)。

## 目录结构
- `src/`：
  - `Swagger.php`：主入口类
  - `RouteAnnotation/`：路由注解解析
    - `Reader.php`：注解读取器
    - `Processors/`：各种文档处理器（ExpandDTO/ExpandEnum/ExpandEloquentModel/XSchema 等）
    - `DTO/`：路由配置相关 DTO
  - `Overwrite/`：覆盖 swagger-php 默认行为（Generator/Analysis/ReflectionAnalyser/Processors/Analysers）
  - `Controller/`：
    - `OpenapiController.php`：提供 OpenAPI JSON 及 Swagger UI 页面
  - `DTO/`：各配置 DTO（OpenapiDoc/SwaggerUi/HostForbidden/RegisterRoute）
  - `Enums/`：PropertyInEnum
  - `Helper/`：SwaggerHelper/ConfigHelper/ArrayHelper/JsExpression
  - `Middleware/`：HostForbiddenMiddleware
  - `view/`：swagger-ui.php 视图模板
- `copy/`：配置文件模板
- `src/Install.php`：Webman 安装脚本

测试文件位于项目根目录的 `tests/Unit/Swagger/`。测试环境配置和 Helper 函数详见根目录 [AGENTS.md](../../AGENTS.md) 的测试相关章节。

## 代码风格

与根项目保持一致，详见根目录 [AGENTS.md](../../AGENTS.md)。

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
