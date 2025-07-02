<?php

namespace WebmanTech\Swagger\Controller;

use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;
use OpenApi\Pipeline;
use OpenApi\Processors as OAProcessors;
use Symfony\Component\Finder\Finder;
use Throwable;
use Webman\Http\Response;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO;
use WebmanTech\Swagger\Helper\JsExpression;
use WebmanTech\Swagger\RouteAnnotation\Analysers\Analysis;
use WebmanTech\Swagger\RouteAnnotation\Analysers\ReflectionAnalyser;
use WebmanTech\Swagger\RouteAnnotation\Processors;

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
        $config = ConfigSwaggerUiDTO::fromConfig($config);

        $data = $config->data;
        $data['ui_config']['url'] = new JsExpression("window.location.pathname + '/{$docRoute}'");

        return raw_view($config->view, $data, $config->view_path);
    }

    private static array $docCache = [];

    /**
     * @throws Throwable
     */
    public function openapiDoc(ConfigOpenapiDocDTO|array $config = []): Response
    {
        $config = ConfigOpenapiDocDTO::fromConfig($config);

        $cacheKey = $config->getCacheKey();

        if (!isset(self::$docCache[$cacheKey])) {
            $openapi = $this->scanAndGenerateOpenapi($config->scan_path, $config->scan_exclude);

            $config->applyModify($openapi);

            $result = $config->generateWithFormat($openapi);

            self::$docCache[$cacheKey] = $result;
        }
        [$content, $contentType] = self::$docCache[$cacheKey];

        return response($content, 200, [
            'Content-Type' => $contentType,
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
            $generator = (new Generator())
                ->setAliases(Generator::DEFAULT_ALIASES)
                ->setNamespaces(Generator::DEFAULT_NAMESPACES)
                ->setAnalyser(new ReflectionAnalyser())
                ->withProcessorPipeline(function (Pipeline $pipeline): void {
                    $pipeline
                        ->remove(null, function (&$pipe) {
                            // 替换实现
                            if ($pipe instanceof OAProcessors\AugmentSchemas) {
                                $pipe = new Processors\AugmentSchemas();
                            }
                            return true;
                        })
                        ->add(new Processors\DTOValidationRulesProcessor())
                        ->add(new Processors\MergeClassInfoProcessor())
                        ->add(new Processors\XSchemaRequestProcessor())
                        ->add(new Processors\XSchemaResponseProcessor())
                        ->add(new Processors\AppendResponseProcessor())
                        ->add(new Processors\XRouteCleanProcessor())
                        ->add(new Processors\SortComponentsProcessor());
                });
            $analysis = new Analysis([], new Context([
                'version' => $generator->getVersion(),
                'logger' => $generator->getLogger(),
            ]));
            $openapi = $generator
                ->generate(
                    Finder::create()
                        ->files()
                        ->followLinks()
                        ->name('*.php')
                        ->in($scanPath)
                        ->notPath($scanExclude ?? []),
                    $analysis,
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
