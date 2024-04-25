<?php

namespace WebmanTech\Swagger\Controller;

use Closure;
use Doctrine\Common\Annotations\Annotation;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Util;
use Symfony\Component\Finder\Finder;
use Throwable;
use Webman\Http\Response;
use WebmanTech\Swagger\Helper\ArrayHelper;
use WebmanTech\Swagger\Helper\JsExpression;

class OpenapiController
{
    private $canUseAnnotations;
    private $canUseAttributes;
    private $requiredElements;

    public function __construct()
    {
        $this->canUseAnnotations = class_exists(Annotation::class);
        $this->canUseAttributes = class_exists(\Attribute::class);

        if (!$this->canUseAnnotations && !$this->canUseAttributes) {
            throw new \Exception('Please install doctrine/annotations or use php>=8.0');
        }

        $this->requiredElements = [
            'info' => $this->canUseAnnotations ? __DIR__ . '/RequiredElementsAnnotations/Info' : __DIR__ . '/RequiredElementsAttributes/Info',
            'pathItem' => $this->canUseAnnotations ? __DIR__ . '/RequiredElementsAnnotations/PathItem' : __DIR__ . '/RequiredElementsAttributes/PathItem',
        ];
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

    private static $docCache = [];

    public function openapiDoc(array $config = []): Response
    {
        $config = ArrayHelper::merge(
            [
                'scan_path' => [], // 扫描的目录
                'scan_exclude' => null, // 扫描忽略的
                'modify' => null, // 修改 $openapi 对象
                'cache_key' => null, // 如何缓存
            ],
            config('plugin.webman-tech.swagger.app.openapi_doc', []),
            $config
        );

        if (is_callable($config['cache_key'])) {
            $config['cache_key'] = $config['cache_key']();
        }
        $cacheKey = $config['cache_key'] ?: __CLASS__;

        if (!isset(static::$docCache[$cacheKey])) {
            $openapi = $this->scanAndGenerateOpenapi($config['scan_path'], $config['scan_exclude']);

            if ($config['modify'] instanceof Closure) {
                $config['modify']($openapi);
            }

            $yaml = $openapi->toYaml();

            static::$docCache[$cacheKey] = $yaml;
        }
        $yaml = static::$docCache[$cacheKey];

        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }

    /**
     * 扫描并生成 yaml
     * @param string|array|Finder $scanPath
     * @param array|null|string $scanExclude
     * @param int $errorCount
     * @return OA\OpenApi
     * @throws Throwable
     */
    private function scanAndGenerateOpenapi($scanPath, $scanExclude = null, int $errorCount = 0): OA\OpenApi
    {
        $requiredElements = $this->requiredElements;

        if (is_string($scanPath)) {
            $scanPath = [$scanPath];
        }
        if (is_array($scanPath) && !$scanPath) {
            $scanPath = array_values($requiredElements);
        }

        try {
            return Generator::scan(Util::finder($scanPath, $scanExclude));
        } catch (Throwable $e) {
            if ($errorCount > count($requiredElements)) {
                throw $e;
            }

            // http://zircote.github.io/swagger-php/guide/required-elements.html
            if ($e->getMessage() === 'Required @OA\Info() not found') {
                if (is_array($scanPath)) {
                    $scanPath = array_merge($scanPath, [$requiredElements['info']]);
                }
                return $this->scanAndGenerateOpenapi($scanPath, $scanExclude, $errorCount + 1);
            }
            if ($e->getMessage() === 'Required @OA\PathItem() not found') {
                if (is_array($scanPath)) {
                    $scanPath = array_merge($scanPath, [$requiredElements['pathItem']]);
                }
                return $this->scanAndGenerateOpenapi($scanPath, $scanExclude, $errorCount + 1);
            }

            throw $e;
        }
    }
}