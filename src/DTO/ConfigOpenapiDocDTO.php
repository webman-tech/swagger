<?php

namespace WebmanTech\Swagger\DTO;

use Closure;
use OpenApi\Annotations\OpenApi;
use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;

final class ConfigOpenapiDocDTO extends BaseConfigDTO
{
    public function __construct(
        public string|array        $scan_path = [], // 扫描的目录
        public null|array          $scan_exclude = null, // 扫描忽略的
        public null|Closure        $modify = null, // 修改 $openapi 对象
        public null|string|Closure $cache_key = null, // 缓存用的 key，当注册不同实例时，需要指定不同的 key，或者做热更新用
        public string              $format = 'yaml', // yaml/json
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
