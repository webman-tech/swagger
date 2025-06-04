<?php

namespace WebmanTech\Swagger\RouteAnnotation;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;
use Symfony\Component\Finder\Finder;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\RouteAnnotation\Analysers\ReflectionAnalyser;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;

class Reader
{
    private $context;
    private $analyser;
    private $generator;
    private $pathItemOperationAttributes = [
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
     * @param $pathOrFile
     * @return array<string, RouteConfigDTO>
     */
    public function getData($pathOrFile): array
    {
        $analysis = new Analysis([], $this->context);

        foreach ($this->formatPath($pathOrFile) as $file) {
            $analysis->addAnalysis($this->analyser->fromFile($file->getRealPath(), $this->context));
        }

        $this->generator->getProcessorPipeline()->process($analysis);
        $openapi = $analysis->openapi;

        $data = [];
        if (!Generator::isDefault($openapi->paths)) {
            foreach ($openapi->paths as $path) {
                foreach ($this->pathItemOperationAttributes as $attr) {
                    $operation = $path->{$attr};
                    if (Generator::isDefault($operation)) {
                        continue;
                    }
                    if (!$operation instanceof OA\Operation) {
                        throw new \InvalidArgumentException(sprintf('"%s" is not an Operation', $attr));
                    }
                    $routeConfig = $this->parseRouteConfig($operation);
                    $data[$routeConfig->method . ':' . $routeConfig->path] = $routeConfig;
                }
            }
        }

        return $data;
    }

    private function formatPath($pathOrFile)
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
        $desc = Generator::isDefault($operation->summary) ? '' : $operation->summary;
        if (!Generator::isDefault($operation->description)) {
            $desc = $desc ? $desc . "({$operation->description})" : $operation->description;
        }

        $x = Generator::isDefault($operation->x) ? [] : $operation->x;

        return new RouteConfigDTO([
            'desc' => $desc,
            'method' => strtoupper($operation->method),
            'path' => $x[SchemaConstants::X_PATH] ?? $operation->path,
            'controller' => implode('\\', array_filter([
                $operation->_context->namespace ?? '',
                $operation->_context->class ?? '',
            ])),
            'action' => $operation->_context->method ?? '',
            'name' => $x[SchemaConstants::X_NAME] ?? null,
            'middlewares' => $x[SchemaConstants::X_MIDDLEWARE] ?? null,
        ]);
    }
}
