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

            $openapi = $this->scanAndGenerateOpenapi($generator, $config->scan_path, $config->scan_exclude, validate: $config->openapi_validate);
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
    private function scanAndGenerateOpenapi(
        Generator         $generator,
        array|string      $scanPath,
        array|string|null $scanExclude = null,
        bool              $validate = false,
        bool              $isRescan = false,
    ): OA\OpenApi
    {
        $requiredElements = $this->requiredElements;

        if (is_string($scanPath)) {
            $scanPath = [$scanPath];
        }
        if (!$scanPath) {
            $scanPath = array_values($requiredElements);
        }

        $openapi = $generator->generate(
            Finder::create()
                ->files()
                ->followLinks()
                ->name('*.php')
                ->in($scanPath)
                ->notPath($scanExclude ?? []),
            validate: false, // 固定为关闭，在后面再执行验证
        );
        if ($openapi === null) {
            throw new \Exception('openapi generate failed');
        }
        if (!$isRescan) {
            // 首次扫描后检查必须的元素是否存在，不存在的话再次扫描
            $requiredScan = [];
            if (Generator::isDefault($openapi->info)) {
                $requiredScan[] = $requiredElements['info'];
            }
            if (Generator::isDefault($openapi->paths)) {
                $requiredScan[] = $requiredElements['pathItem'];
            }
            if ($requiredScan) {
                $scanPath = array_merge($scanPath, $requiredScan);
                $openapi = $this->scanAndGenerateOpenapi($generator, $scanPath, $scanExclude, validate: false, isRescan: true);
            }
        }

        if ($validate) {
            $openapi->validate();
        }

        return $openapi;
    }
}
