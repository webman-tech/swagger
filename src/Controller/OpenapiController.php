<?php

namespace WebmanTech\Swagger\Controller;

use InvalidArgumentException;
use OpenApi\Generator;
use OpenApi\Util;
use Webman\Http\Response;
use WebmanTech\Swagger\Helper\ArrayHelper;
use WebmanTech\Swagger\Helper\JsExpression;

class OpenapiController
{
    private $cacheKey;
    private static $memoryCached = [];

    public function __construct()
    {
        $this->cacheKey = uniqid();
    }

    public function swaggerUI(string $docRoute, array $config = []): Response
    {
        $config = ArrayHelper::merge(
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
            config('plugin.webman-tech.swagger.app.swagger_ui', []),
            $config
        );
        $config['data']['ui_config']['url'] = new JsExpression("window.location.pathname + '/{$docRoute}'");

        return raw_view($config['view'], $config['data'], $config['view_path']);
    }

    public function openapiDoc(array $config = []): Response
    {
        $config = ArrayHelper::merge(
            [
                'scan_path' => [],
                'scan_exclude' => null,
            ],
            config('plugin.webman-tech.swagger.app.openapi_doc', []),
            $config
        );

        if (!isset(static::$memoryCached[$this->cacheKey])) {
            if (!$config['scan_path']) {
                throw new InvalidArgumentException('openapi_doc.scan_path must be set');
            }
            $openapi = Generator::scan(Util::finder($config['scan_path'], $config['scan_exclude']));
            $yaml = $openapi->toYaml();

            static::$memoryCached[$this->cacheKey] = $yaml;
        }
        $yaml = static::$memoryCached[$this->cacheKey];

        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }
}