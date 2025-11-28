<?php

namespace WebmanTech\Swagger\Controller;

use Exception;
use OpenApi\Annotations as OA;
use RuntimeException;
use Throwable;
use WebmanTech\CommonUtils\Local;
use WebmanTech\CommonUtils\Response;
use WebmanTech\CommonUtils\View;
use WebmanTech\Swagger\Controller\RequiredElementsAttributes\PathItem\OpenapiSpec;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\DTO\ConfigSwaggerUiDTO;
use WebmanTech\Swagger\Helper\ConfigHelper;
use WebmanTech\Swagger\Helper\JsExpression;
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

        $content = View::renderPHP(Local::combinePath($config->view_path, $config->view), $data);
        return Response::make()
            ->withHeaders(['Content-Type' => 'text/html; charset=utf-8'])
            ->withBody($content)
            ->getRaw();
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

        return Response::make()
            ->withHeaders(['Content-Type' => $contentType])
            ->withBody($content)
            ->getRaw();
    }

    /**
     * DTO 生成器页面
     */
    public function dtoGenerator(array|null $dtoGeneratorConfig = null): mixed
    {
        $path = ConfigHelper::getDtoGeneratorWebPath();
        if ($path === null) {
            throw new RuntimeException('DTO generator assets not found. Please install webman-tech/dto.');
        }
        $content = file_get_contents($path);

        if ($dtoGeneratorConfig) {
            $dtoGeneratorConfig = json_encode($dtoGeneratorConfig);
            $prefix = <<<JS
<script>
window.__DTO_GENERATOR_CONFIG = {$dtoGeneratorConfig};
</script>
JS;
            $content = str_replace('</head>', $prefix . '</head>', $content);
        }
        return Response::make()
            ->withHeaders(['Content-Type' => 'text/html; charset=utf-8'])
            ->withBody($content)
            ->getRaw();
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
            throw new Exception('openapi generate failed');
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
