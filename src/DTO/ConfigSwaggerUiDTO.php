<?php

namespace WebmanTech\Swagger\DTO;

use WebmanTech\Swagger\Helper\ArrayHelper;
use WebmanTech\Swagger\Helper\ConfigHelper;

/**
 * @property string $view 视图名称
 * @property string $view_path 视图路径，相对 app_path() 的路径
 * @property array $data 视图数据
 */
class ConfigSwaggerUiDTO extends BaseDTO
{
    protected function initData(): void
    {
        $this->_data = ArrayHelper::merge(
            [
                'view' => 'swagger-ui',
                'view_path' => '../vendor/webman-tech/swagger/src', // 相对 app_path() 的路径
                'data' => [
                    // @link https://github.com/swagger-api/swagger-ui/blob/master/dist/swagger-initializer.js
                    'css' => [
                        'https://unpkg.com/swagger-ui-dist/swagger-ui.css',
                        //'https://unpkg.com/swagger-ui-dist/index.css',
                    ],
                    'js' => [
                        'https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js',
                        //'https://unpkg.com/swagger-ui-dist/swagger-ui-standalone-preset.js',
                    ],
                    'title' => config('app.name', 'swagger') . ' - openapi',
                    'ui_config' => [
                        // @link https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/
                        'dom_id' => '#swagger-ui',
                        'persistAuthorization' => true,
                        'deepLinking' => true,
                        'filter' => '',
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
            ],
            ConfigHelper::get('app.swagger_ui', []),
            $this->_data
        );
    }
}
