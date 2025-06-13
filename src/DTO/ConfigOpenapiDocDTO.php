<?php

namespace WebmanTech\Swagger\DTO;

use OpenApi\Annotations\OpenApi;
use WebmanTech\Swagger\Helper\ArrayHelper;
use WebmanTech\Swagger\Helper\ConfigHelper;

/**
 * @property string|array $scan_path 扫描的目录
 * @property null|array $scan_exclude 扫描忽略的
 * @property null|callable $modify 修改 $openapi 对象
 * @property null|string|callable $cache_key 缓存用的 key，当注册不同实例时，需要指定不同的 key，或者做热更新用
 * @property string $format yaml/json
 */
class ConfigOpenapiDocDTO extends BaseDTO
{
    protected function initData(): void
    {
        $this->_data = ArrayHelper::merge(
            [
                'scan_path' => [],
                'scan_exclude' => null,
                'modify' => null,
                'cache_key' => null,
                'format' => 'yaml', // yaml/json
            ],
            ConfigHelper::get('app.openapi_doc', []),
            $this->_data
        );
    }

    public function getCacheKey(): string
    {
        $cacheKey = null;
        if (is_string($this->cache_key)) {
            $cacheKey = $this->cache_key;
        } elseif (is_callable($this->cache_key)) {
            $cacheKey = call_user_func($this->cache_key);
        }

        return $cacheKey ?? md5(serialize($this->scan_path));
    }

    public function applyModify(OpenApi $openapi): void
    {
        if (is_callable($this->modify)) {
            call_user_func($this->modify, $openapi);
        }
    }

    public function generateWithFormat(OpenApi $openApi): array
    {
        return [
            'yaml' => [
                $openApi->toYaml(),
                'application/x-yaml',
            ],
            'json' => [
                $openApi->toJson(),
                'application/json',
            ],
        ][$this->format];
    }
}
