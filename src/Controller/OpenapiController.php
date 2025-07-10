<?php

namespace WebmanTech\Swagger\Controller;

use OpenApi\Annotations as OA;
use Symfony\Component\Finder\Finder;
use Throwable;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO;
use WebmanTech\Swagger\Helper\JsExpression;
use WebmanTech\Swagger\Integrations\Response;
use WebmanTech\Swagger\Overwrite\Generator;

final class OpenapiController
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
    public function swaggerUI(string $docRoute, ConfigSwaggerUiDTO|array $config = [])
    {
        $config = ConfigSwaggerUiDTO::fromConfig($config);

        $data = $config->data;
        $data['ui_config']['url'] = new JsExpression("window.location.pathname + '/{$docRoute}'");

        return Response::create()->renderView($config->view, $data, $config->view_path);
    }

    private static array $docCache = [];

    /**
     * @throws Throwable
     */
    public function openapiDoc(ConfigOpenapiDocDTO|array $config = [])
    {
        $config = ConfigOpenapiDocDTO::fromConfig($config);

        $cacheKey = $config->getCacheKey();

        if (!isset(self::$docCache[$cacheKey])) {
            $generator = (new Generator($config))->init();
            $config->applyGenerator($generator);

            $openapi = $this->scanAndGenerateOpenapi($generator, $config->scan_path, $config->scan_exclude);
            $config->applyModify($openapi);

            $result = $config->generateWithFormat($openapi);

            self::$docCache[$cacheKey] = $result;
        }
        [$content, $contentType] = self::$docCache[$cacheKey];

        return Response::create()->body($content, ['Content-Type' => $contentType]);
    }

    /**
     * 扫描并生成 yaml
     * @throws Throwable
     */
    private function scanAndGenerateOpenapi(Generator $generator, array|string $scanPath, array|string|null $scanExclude = null, int $errorCount = 0): OA\OpenApi
    {
        $requiredElements = $this->requiredElements;

        if (is_string($scanPath)) {
            $scanPath = [$scanPath];
        }
        if (!$scanPath) {
            $scanPath = array_values($requiredElements);
        }

        try {
            $openapi = $generator->generate(
                Finder::create()
                    ->files()
                    ->followLinks()
                    ->name('*.php')
                    ->in($scanPath)
                    ->notPath($scanExclude ?? []),
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
                return $this->scanAndGenerateOpenapi($generator, $scanPath, $scanExclude, $errorCount + 1);
            }
            if ($e->getMessage() === 'Required @OA\PathItem() not found') {
                $scanPath = array_merge($scanPath, [$requiredElements['pathItem']]);
                return $this->scanAndGenerateOpenapi($generator, $scanPath, $scanExclude, $errorCount + 1);
            }

            throw $e;
        }
    }
}
