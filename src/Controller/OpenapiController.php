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

            $openapi = $this->scanAndGenerateOpenapi($generator, $config->getScanSources(), validate: $config->openapi_validate);
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
        Generator $generator,
        iterable  $scanSources,
        bool      $validate = false,
        bool      $isRescan = false,
    ): OA\OpenApi
    {
        $openapi = $generator->generate(
            $scanSources,
            validate: false, // 固定为关闭，在后面再执行验证
        );
        if ($openapi === null) {
            throw new \Exception('openapi generate failed');
        }
        if (!$isRescan) {
            // 首次扫描后检查必须的元素是否存在，不存在的话再次扫描
            $requiredScan = [];
            if (Generator::isDefault($openapi->info)) {
                $requiredScan[] = $this->requiredElements['info'];
            }
            if (Generator::isDefault($openapi->paths)) {
                $requiredScan[] = $this->requiredElements['pathItem'];
            }
            if ($requiredScan) {
                $openapi = $this->scanAndGenerateOpenapi(
                    $generator,
                    [
                        $scanSources,
                        Finder::create()
                            ->in($requiredScan),
                    ],
                    validate: false,
                    isRescan: true,
                );
            }
        }

        if ($validate) {
            $openapi->validate();
        }

        return $openapi;
    }
}
