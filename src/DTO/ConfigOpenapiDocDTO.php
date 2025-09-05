<?php

namespace WebmanTech\Swagger\DTO;

use Closure;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use Symfony\Component\Finder\Finder;
use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\DTO\BaseDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;

final class ConfigOpenapiDocDTO extends BaseConfigDTO
{
    public bool $openapi_validate;
    public array $auto_load_schema_classes;
    public iterable $scan_path;

    public function __construct(
        string|iterable|Closure    $scan_path = [], // 扫描的目录
        public null|array          $scan_exclude = null, // 扫描忽略的
        public null|Closure        $generator_modify = null, // 修改 $generator 对象
        public null|Closure        $modify = null, // 修改 $openapi 对象
        public null|string|Closure $cache_key = null, // 缓存用的 key，当注册不同实例时，需要指定不同的 key，或者做热更新用
        public string              $format = 'yaml', // yaml/json
        null|bool                  $openapi_validate = null, // 是否校验产出的 openapi 文档
        public null|true|Closure   $schema_name_format_use_classname = null, // schema 的名称是否使用完整的类名（swagger-php 默认取类的名字，不带 namespace）
        public bool                $schema_enum_description_enable = true, // 提取 enum 的描述信息开关
        public null|string         $schema_enum_description_method = null, // 指定提取 enum 的描述信息的方法名
        public bool                $expand_eloquent_model_enable = true, // 是否自动扫描 Eloquent Model 的属性，并生成对应的 schema
        public bool                $clean_unused_components_enable = false, // 是否清理未使用的 components
        null|array                 $auto_load_schema_classes = null, // 自动加载 schema 类型的 class 名集合，可以在不需要写 Schema 注解的情况下也能当 Schema 用
        public null|string         $response_layout_class = null, // response 的结构 class
        public null|string         $response_layout_data_code = null, // response 的结构 class 中的 data 字段
    )
    {
        $this->auto_load_schema_classes = $auto_load_schema_classes ?? array_filter([
            BaseDTO::class,
            $this->expand_eloquent_model_enable ? Model::class : null,
        ]);

        // 暂时默认不开启校验，由于以下 issue 问题
        // https://github.com/zircote/swagger-php/pull/1776
        $this->openapi_validate = $openapi_validate ?? false;

        if ($scan_path instanceof Closure) {
            $scan_path = $scan_path();
        }
        if (is_string($scan_path) || $scan_path instanceof Finder) {
            $scan_path = [$scan_path];
        }
        $this->scan_path = $scan_path;
    }

    protected static function getAppConfig(): array
    {
        return ConfigHelper::get('app.openapi_doc', []);
    }

    public function getScanSources(): iterable
    {
        /** @var array $scanPaths */
        $scanPaths = $this->scan_path;
        // 区分 files 和 path
        $files = [];
        $finders = [];
        $paths = [];
        foreach ($scanPaths as $path) {
            if ($path instanceof Finder) {
                $finders[] = $path;
                continue;
            }
            if (is_file($path)) {
                $files[] = $path;
                continue;
            }
            $paths[] = $path;
        }

        return [
            $files,
            $finders,
            $paths
                ? Finder::create()
                ->files()
                ->followLinks()
                ->name('*.php')
                ->in($paths)
                ->exclude($this->scan_exclude ?? [])
                : []
        ];
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
