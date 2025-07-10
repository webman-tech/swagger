<?php

namespace WebmanTech\Swagger\DTO;

use Closure;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;

final class ConfigOpenapiDocDTO extends BaseConfigDTO
{
    public function __construct(
        public string|array        $scan_path = [], // 扫描的目录
        public null|array          $scan_exclude = null, // 扫描忽略的
        public null|Closure        $generator_modify = null, // 修改 $generator 对象
        public null|Closure        $modify = null, // 修改 $openapi 对象
        public null|string|Closure $cache_key = null, // 缓存用的 key，当注册不同实例时，需要指定不同的 key，或者做热更新用
        public string              $format = 'yaml', // yaml/json
        public null|true|Closure   $schema_name_format_use_classname = null, // schema 的名称是否使用完整的类名（swagger-php 默认取类的名字，不带 namespace）
        public bool                $schema_enum_description_enable = true, // 提取 enum 的描述信息开关
        public null                $schema_enum_description_method = null, // 指定提取 enum 的描述信息的方法名
        public bool                $expand_eloquent_model_enable = true, // 是否自动扫描 Eloquent Model 的属性，并生成对应的 schema
    )
    {
    }

    protected static function getAppConfig(): array
    {
        return ConfigHelper::get('app.openapi_doc', []);
    }

    public function getCacheKey(): string
    {
        $cacheKey = null;
        if (is_string($this->cache_key)) {
            $cacheKey = $this->cache_key;
        } elseif ($this->cache_key instanceof Closure) {
            $cacheKey = call_user_func($this->cache_key);
        }

        return $cacheKey ?? md5(serialize($this->scan_path));
    }

    public function applyGenerator(Generator $generator): void
    {
        if ($this->generator_modify instanceof Closure) {
            call_user_func($this->generator_modify, $generator);
        }
    }

    public function applyModify(OpenApi $openapi): void
    {
        if ($this->modify instanceof Closure) {
            call_user_func($this->modify, $openapi);
        }
    }

    public function generateWithFormat(OpenApi $openApi): array
    {
        return match ($this->format) {
            'json' => [
                $openApi->toJson(),
                'application/json',
            ],
            default => [
                $openApi->toYaml(),
                'application/x-yaml',
            ],
        };
    }
}
