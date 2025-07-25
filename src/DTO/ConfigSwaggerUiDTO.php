<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\DTO\BaseConfigDTO;
use WebmanTech\Swagger\Helper\ArrayHelper;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Helper\JsExpression;

final class ConfigSwaggerUiDTO extends BaseConfigDTO
{
    public string $view_path;

    public function __construct(
        public string               $view = 'swagger-ui', // 视图名称
        null|string                 $view_path = null, // 视图路径，相对 app_path() 的路径
        private string              $assets_base_url = 'https://unpkg.com/swagger-ui-dist',
        private readonly null|array $tag_sort = null, // 标签的排序
        public array                $data = [], // 视图数据
    )
    {
        $this->view_path = $view_path ?? ConfigHelper::getViewPath();
        $appName = (string)config('app.name', 'swagger');

        $this->assets_base_url = rtrim($this->assets_base_url, '/');
        $tagsSorter = null;
        if ($this->tag_sort) {
            $order = json_encode(array_values($this->tag_sort), JSON_UNESCAPED_UNICODE);
            $tagsSorter = new JsExpression(<<<JS
function (a, b) {
    const order = {$order};
    const indexA = order.indexOf(a)
    const indexB = order.indexOf(b)
    if (indexA !== -1 && indexB !== -1) {
        return indexA - indexB;
    }
    if (indexA !== -1) {
        return -1
    }
    if (indexB !== -1) {
        return 1
    }
    return a.localeCompare(b);
}
JS
            );
        }
        $this->data = ArrayHelper::merge(
            [
                // @link https://github.com/swagger-api/swagger-ui/blob/master/dist/swagger-initializer.js
                'css' => [
                    '/swagger-ui.css', // 修改 cdn 地址
                    //'index.css',
                ],
                'js' => [
                    '/swagger-ui-bundle.js',
                    //'swagger-ui-standalone-preset.js',
                ],
                'title' => $appName . ' - openapi',
                'ui_config' => [
                    // @link https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/
                    'dom_id' => '#swagger-ui',
                    'persistAuthorization' => true,
                    'deepLinking' => true,
                    'filter' => '',
                    'tagsSorter' => $tagsSorter,
                    /*'presets' => [
                        new JsExpression('SwaggerUIBundle.presets.apis'),
                        new JsExpression('SwaggerUIStandalonePreset'),
                    ],
                    'plugins' => [
                        new JsExpression('SwaggerUIBundle.plugins.DownloadUrl'),
                    ],
                    'layout' => 'StandaloneLayout',*/
                ],
            ],
            $this->data,
        );
        $this->data['css'] = $this->processAssetUrl($this->data['css']);
        $this->data['js'] = $this->processAssetUrl($this->data['js']);
    }

    protected static function getAppConfig(): array
    {
        return ConfigHelper::get('app.swagger_ui', []);
    }

    private function processAssetUrl(array $data): array
    {
        return array_map(function (string $item) {
            if (!str_starts_with($item, 'https://') && !str_starts_with($item, 'http://')) {
                $item = $this->assets_base_url . '/' . ltrim($item, '/');
            }
            return $item;
        }, $data);
    }
}
