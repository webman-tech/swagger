<?php

namespace WebmanTech\Swagger\Controller;

use OpenApi\Annotations as OA;
use Throwable;
use WebmanTech\Swagger\Controller\RequiredElementsAttributes\PathItem\OpenapiSpec;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
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
        $data['dto_generator_url'] ??= null;
        $data['dto_generator_config'] ??= [
            'defaultGenerationType' => 'form',
            'defaultNamespace' => 'app\\controller\\api\\form',
        ];

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
     * DTO 生成器页面
     */
    public function dtoGenerator(array|null $dtoGeneratorConfig = null): mixed
    {
        $basePath = ConfigHelper::getDtoGeneratorPath();
        if ($basePath === null) {
            throw new \RuntimeException('DTO generator assets not found. Please install webman-tech/dto.');
        }
        $indexPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        if (!is_file($indexPath)) {
            throw new \RuntimeException('DTO generator index.html not found.');
        }
        $content = file_get_contents($indexPath);
        if ($content === false) {
            throw new \RuntimeException('Failed to read DTO generator index.html');
        }

        if ($dtoGeneratorConfig) {
            $dtoGeneratorConfig = json_encode($dtoGeneratorConfig);
            $prefix = <<<JS
<script>
window.__DTO_GENERATOR_CONFIG = {$dtoGeneratorConfig};
</script>
JS;
            $content = str_replace('</head>', $prefix . '</head>', $content);
        }
        return Response::create()->body($content, ['Content-Type' => 'text/html; charset=utf-8']);
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
            $openapi->paths = array_filter($openapi->paths, fn(OA\PathItem $pathItem) => $pathItem->path !== OpenapiSpec::EXAMPLE_PATH);
        }

        if ($validate) {
            $openapi->validate();
        }

        return $openapi;
    }
}
