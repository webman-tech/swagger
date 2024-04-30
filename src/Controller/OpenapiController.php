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
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO;
use WebmanTech\Swagger\Helper\JsExpression;
use WebmanTech\Swagger\RouteAnnotation\Processors\CleanRouteX;

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

    /**
     * @param string $docRoute
     * @param array|ConfigOpenapiDocDTO $config
     * @return Response
     * @throws Throwable
     */
    public function swaggerUI(string $docRoute, $config = []): Response
    {
        if (!$config instanceof ConfigSwaggerUiDTO) {
            $config = new ConfigSwaggerUiDTO($config);
        }

        $tempData = $config->data;
        $tempData['ui_config']['url'] = new JsExpression("window.location.pathname + '/{$docRoute}'");
        $config->data = $tempData;

        return raw_view($config->view, $config->data, $config->view_path);
    }

    private static $docCache = [];

    /**
     * @param array|ConfigOpenapiDocDTO $config
     * @return Response
     * @throws Throwable
     */
    public function openapiDoc($config = []): Response
    {
        if (!$config instanceof ConfigOpenapiDocDTO) {
            $config = new ConfigOpenapiDocDTO($config);
        }

        $cacheKey = $config->getCacheKey();

        if (!isset(static::$docCache[$cacheKey])) {
            $openapi = $this->scanAndGenerateOpenapi($config->scan_path, $config->scan_exclude);

            $config->applyModify($openapi);

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
            /**
             * @see Generator::scan
             */
            return (new Generator())
                ->setAliases(Generator::DEFAULT_ALIASES)
                ->setNamespaces(Generator::DEFAULT_NAMESPACES)
                ->addProcessor(new CleanRouteX()) // 清理路由注解
                ->generate(Util::finder($scanPath, $scanExclude));
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