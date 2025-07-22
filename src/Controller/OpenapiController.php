<?php

namespace WebmanTech\Swagger\Controller;

use OpenApi\Annotations as OA;
use Throwable;
use WebmanTech\Swagger\Controller\RequiredElementsAttributes\PathItem\OpenapiSpec;
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
     * Swagger UI 页面展示
     * @throws Throwable
     */
    public function swaggerUI(string $docRoute, ConfigSwaggerUiDTO|array $config = []): mixed
    {
        $config = ConfigSwaggerUiDTO::fromConfig($config);

        $data = $config->data;
        $data['ui_config']['url'] = new JsExpression("window.location.pathname.replace(/\/+$/, '') + '/{$docRoute}'");

        return Response::create()->renderView($config->view, $data, $config->view_path);
    }

    private static array $docCache = [];

    /**
     * Openapi 文档
     * @throws Throwable
     */
    public function openapiDoc(ConfigOpenapiDocDTO|array $config = []): mixed
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
    ): OA\OpenApi
    {
        $openapi = $generator->generate(
            [
                $this->requiredElements,
                $scanSources,
            ],
            validate: false, // 固定为关闭，在后面再执行验证
        );
        if ($openapi === null) {
            throw new \Exception('openapi generate failed');
        }
        if (count($openapi->paths) > 1) {
            // 表示已经有接口了，移除掉默认的必须路径
            $openapi->paths = array_filter($openapi->paths, function (OA\PathItem $pathItem) {
                return $pathItem->path !== OpenapiSpec::EXAMPLE_PATH;
            });
        }

        if ($validate) {
            $openapi->validate();
        }

        return $openapi;
    }
}
