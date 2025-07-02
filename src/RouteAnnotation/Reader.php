<?php

namespace WebmanTech\Swagger\RouteAnnotation;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;
use Symfony\Component\Finder\Finder;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation\Analysers\ReflectionAnalyser;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;

/**
 * 读取路由信息
 */
final class Reader
{
    private readonly Context $context;
    private readonly ReflectionAnalyser $analyser;
    private readonly Generator $generator;
    private array $pathItemOperationAttributes = [
        'get',
        'post',
        'put',
        'delete',
        'patch',
        //'trace', // webman 暂不支持
        'head',
        'options',
    ];

    public function __construct()
    {
        $this->context = new Context();
        $this->analyser = new ReflectionAnalyser();
        $this->generator = new Generator();
    }

    /**
     * 通过 swagger 文档读取路由信息
     * @return array<string, RouteConfigDTO>
     */
    public function getData(array|string $pathOrFile): array
    {
        $analysis = new Analysis([], $this->context);

        foreach ($this->formatPath($pathOrFile) as $file) {
            $analysis->addAnalysis($this->analyser->fromFile($file->getRealPath(), $this->context));
        }

        $this->generator->getProcessorPipeline()->process($analysis);
        $openapi = $analysis->openapi;
        if ($openapi === null) {
            return [];
        }

        $data = [];
        if (!Generator::isDefault($openapi->paths)) {
            foreach ($openapi->paths as $path) {
                foreach ($this->pathItemOperationAttributes as $method) {
                    /** @var string|OA\Operation $operation */
                    $operation = $path->{$method};
                    if (Generator::isDefault($operation)) {
                        continue;
                    }
                    if (!$operation instanceof OA\Operation) {
                        throw new \InvalidArgumentException(sprintf('"%s" is not an Operation', $method));
                    }
                    $routeConfig = $this->parseRouteConfig($operation);
                    $data[$routeConfig->method . ':' . $routeConfig->path] = $routeConfig;
                }
            }
        }

        return $data;
    }

    /**
     * @return \SplFileInfo[]|Finder
     */
    private function formatPath(array|string $pathOrFile): array|Finder
    {
        if (is_string($pathOrFile)) {
            if (is_file($pathOrFile)) {
                return [
                    new \SplFileInfo($pathOrFile)
                ];
            }
            if (!is_dir($pathOrFile)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid path or file', $pathOrFile));
            }
            $pathOrFile = [$pathOrFile];
        }

        return Finder::create()->files()->in($pathOrFile)->name('*.php');
    }

    private function parseRouteConfig(OA\Operation $operation): RouteConfigDTO
    {
        $summary = SwaggerHelper::getValue($operation->summary, '');
        $description = SwaggerHelper::getValue($operation->description, '');
        if ($summary && $description) {
            $desc = "{$summary}({$description})";
        } else {
            $desc = $summary . $description;
        }
        $x = SwaggerHelper::getValue($operation->x, []);

        return new RouteConfigDTO(
            desc: $desc,
            method: strtoupper($operation->method),
            path: $x[SchemaConstants::X_PATH] ?? $operation->path,
            controller: SwaggerHelper::getAnnotationClassName($operation),
            action: $operation->_context->method ?? '',
            name: $x[SchemaConstants::X_NAME] ?? null,
            middlewares: $x[SchemaConstants::X_MIDDLEWARE] ?? null,
        );
    }
}
