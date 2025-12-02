<?php

namespace WebmanTech\Swagger\Controller;

use Exception;
use OpenApi\Annotations as OA;
use RuntimeException;
use Throwable;
use WebmanTech\CommonUtils\Local;
use WebmanTech\CommonUtils\Request;
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

        // 支持通过 request 传参来强制重新生成
        if ($request = Request::getCurrent()) {
            $generate = (bool)($request->get('generate') ?? false);
            if ($generate) {
                $docRoute .= '?generate=1';
            }
        }

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

    /**
     * Openapi 文档
     * @throws Throwable
     */
    public function openapiDoc(ConfigOpenapiDocDTO|array $config = []): mixed
    {
        $config = ConfigOpenapiDocDTO::fromConfig($config);

        if ($config->max_execute_time) {
            set_time_limit($config->max_execute_time);
        }
        if ($config->max_memory_usage) {
            ini_set('memory_limit', $config->max_memory_usage . 'M');
        }

        $cache = $config->getCache();
        $cacheKey = $config->getCacheKey();

        $generate = false;
        if ($request = Request::getCurrent()) {
            $generate = (bool)($request->get('generate') ?? false);
        }

        [$content, $contentType, $md5] = $cache->get($cacheKey) ?? [null, null, null];
        if ($md5 === null || $generate) {
            $md5 = md5(serialize($config->scan_path) . $config->format);
            try {
                $generator = (new Generator($config))->init();
                $config->applyGenerator($generator);

                $openapi = $this->scanAndGenerateOpenapi($generator, $config->getScanSources(), validate: $config->openapi_validate);
                $config->applyModify($openapi);

                [$content, $contentType] = $config->generateWithFormat($openapi);
            } catch (Throwable $e) {
                if ($config->generate_error_handler) {
                    ($config->generate_error_handler)($e);
                }
                throw $e;
            }

            $cache->set($cacheKey, [$content, $contentType, $md5]);
        }

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
        $content = file_get_contents($path) ?: '';

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
