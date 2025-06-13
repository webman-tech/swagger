<?php

namespace WebmanTech\Swagger\Controller;

use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Pipeline;
use Symfony\Component\Finder\Finder;
use Throwable;
use Webman\Http\Response;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO;
use WebmanTech\Swagger\Helper\JsExpression;
use WebmanTech\Swagger\RouteAnnotation\Analysers\ReflectionAnalyser;
use WebmanTech\Swagger\RouteAnnotation\Processors\AppendResponse;
use WebmanTech\Swagger\RouteAnnotation\Processors\CleanRouteX;
use WebmanTech\Swagger\RouteAnnotation\Processors\MergeClassLevelInfo;
use WebmanTech\Swagger\RouteAnnotation\Processors\SchemaQueryParameter;
use WebmanTech\Swagger\RouteAnnotation\Processors\SchemaRequest;
use WebmanTech\Swagger\RouteAnnotation\Processors\SchemaResponse;

class OpenapiController
{
    private readonly array $requiredElements;

    public function __construct()
    {
        $this->requiredElements = [
            'info' => __DIR__ . '/RequiredElementsAttributes/Info',
            'pathItem' => __DIR__ . '/RequiredElementsAttributes/PathItem',
        ];
    }

    /**
     * @throws Throwable
     */
    public function swaggerUI(string $docRoute, ConfigSwaggerUiDTO|array $config = []): Response
    {
        if (is_array($config)) {
            $config = new ConfigSwaggerUiDTO($config);
        }

        $tempData = $config->data;
        $tempData['ui_config']['url'] = new JsExpression("window.location.pathname + '/{$docRoute}'");
        $config->data = $tempData;

        return raw_view($config->view, $config->data, $config->view_path);
    }

    private static array $docCache = [];

    /**
     * @throws Throwable
     */
    public function openapiDoc(ConfigOpenapiDocDTO|array $config = []): Response
    {
        if (is_array($config)) {
            $config = new ConfigOpenapiDocDTO($config);
        }

        $cacheKey = $config->getCacheKey();

        if (!isset(self::$docCache[$cacheKey])) {
            $openapi = $this->scanAndGenerateOpenapi($config->scan_path, $config->scan_exclude);

            $config->applyModify($openapi);

            $yaml = $openapi->toYaml();

            self::$docCache[$cacheKey] = $yaml;
        }
        $yaml = self::$docCache[$cacheKey];

        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }

    /**
     * 扫描并生成 yaml
     * @throws Throwable
     */
    private function scanAndGenerateOpenapi(array|string $scanPath, array|string|null $scanExclude = null, int $errorCount = 0): OA\OpenApi
    {
        $requiredElements = $this->requiredElements;

        if (is_string($scanPath)) {
            $scanPath = [$scanPath];
        }
        if (!$scanPath) {
            $scanPath = array_values($requiredElements);
        }

        try {
            /**
             * @see Generator::scan
             */
            $openapi = (new Generator())
                ->setAliases(Generator::DEFAULT_ALIASES)
                ->setNamespaces(Generator::DEFAULT_NAMESPACES)
                ->setAnalyser(new ReflectionAnalyser())
                ->withProcessorPipeline(function (Pipeline $pipeline): void {
                    $pipeline
                        ->add(new MergeClassLevelInfo())
                        ->add(new SchemaQueryParameter())
                        ->add(new SchemaRequest())
                        ->add(new SchemaResponse())
                        ->add(new AppendResponse())
                        ->add(new CleanRouteX()) // 清理路由注解
                    ;
                })
                ->generate(
                    Finder::create()
                        ->files()
                        ->followLinks()
                        ->name('*.php')
                        ->in($scanPath)
                        ->notPath($scanExclude ?? [])
                );
            if ($openapi === null) {
                throw new \Exception('openapi generate failed');
            }
            return $openapi;
        } catch (Throwable $e) {
            if ($errorCount > count($requiredElements)) {
                throw $e;
            }

            // http://zircote.github.io/swagger-php/guide/required-elements.html
            if ($e->getMessage() === 'Required @OA\Info() not found') {
                $scanPath = array_merge($scanPath, [$requiredElements['info']]);
                return $this->scanAndGenerateOpenapi($scanPath, $scanExclude, $errorCount + 1);
            }
            if ($e->getMessage() === 'Required @OA\PathItem() not found') {
                $scanPath = array_merge($scanPath, [$requiredElements['pathItem']]);
                return $this->scanAndGenerateOpenapi($scanPath, $scanExclude, $errorCount + 1);
            }

            throw $e;
        }
    }
}
