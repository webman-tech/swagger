<?php

namespace WebmanTech\Swagger\RouteAnnotation;

use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;
use Symfony\Component\Finder\Finder;
use WebmanTech\Swagger\RouteAnnotation\DTO\RequestParamDTO;
use WebmanTech\Swagger\RouteAnnotation\DTO\RequestBodyDTO;
use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;

class Reader
{
    private $context;
    private $analyser;
    private $generator;

    public function __construct()
    {
        $this->context = new Context();
        $this->analyser = new ReflectionAnalyser([
            new DocBlockAnnotationFactory(), new AttributeAnnotationFactory()
        ]);
        $this->generator = new Generator();
    }

    /**
     * @param $pathOrFile
     * @return array|<string, RouteConfigDTO>
     */
    public function getData($pathOrFile): array
    {
        $analysis = new Analysis([], $this->context);

        foreach ($this->formatPath($pathOrFile) as $file) {
            $analysis->addAnalysis($this->analyser->fromFile($file->getRealPath(), $this->context));
        }

        $analysis->process($this->generator->getProcessors());

        $openapi = $analysis->openapi;

        $data = [];
        foreach ($openapi->paths as $path) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (Generator::isDefault($path->{$method})) {
                    continue;
                }
                /** @var OA\Operation $operation */
                $operation = $path->{$method};
                $routeConfig = $this->parseCommon($operation);
                $data[$routeConfig->method . ':' . $routeConfig->path] = $routeConfig;
            }
        }

        return $data;
    }

    private function formatPath($pathOrFile)
    {
        if (is_dir($pathOrFile)) {
            return Finder::create()->files()->in($pathOrFile)->name('*.php');
        }
        if (is_file($pathOrFile)) {
            return [
                new \SplFileInfo($pathOrFile)
            ];
        }
        throw new \InvalidArgumentException(sprintf('"%s" is not a valid path or file', $pathOrFile));
    }

    private function parseCommon(OA\Operation $operation): RouteConfigDTO
    {
        $desc = Generator::isDefault($operation->summary) ? '' : $operation->summary;
        if (!Generator::isDefault($operation->description)) {
            $desc = $desc ? $desc . "({$operation->description})" : $operation->description;
        }

        return new RouteConfigDTO([
            'desc' => $desc,
            'method' => strtoupper($operation->method),
            'path' => $operation->path,
            'controller' => implode('\\', array_filter([
                $operation->_context->namespace ?? '',
                $operation->_context->class ?? '',
            ])),
            'action' => $operation->_context->method ?? '',
            'request_param' => $this->parseRequestParam($operation->parameters),
            'request_body' => $this->parseRequestBody($operation->requestBody),
            'request_body_required' => Generator::isDefault($operation->requestBody)
                ? false :
                (Generator::isDefault($operation->requestBody->required) ? false : $operation->requestBody->required),
        ]);
    }

    /**
     * @param string|OA\Parameter[] $parameters
     * @return array|<string, <string, RequestParamDTO>>
     */
    private function parseRequestParam($parameters): array
    {
        if (Generator::isDefault($parameters)) {
            return [];
        }

        $data = [];

        foreach ($parameters as $parameter) {
            $in = $parameter->in;
            if (Generator::isDefault($parameter->name)) {
                continue;
            }

            $item = new RequestParamDTO([
                'desc' => Generator::isDefault($parameter->description) ? '' : $parameter->description,
                'type' => Generator::isDefault($parameter->schema)
                    ? null
                    : (Generator::isDefault($parameter->schema->type) ? null : $parameter->schema->type),
                'required' => $in === 'path'
                    ? true
                    : (Generator::isDefault($parameter->required) ? false : $parameter->required),
                'nullable' => $in === 'query'
                    ? Generator::isDefault($parameter->allowEmptyValue) ? false : $parameter->allowEmptyValue
                    : null,
            ]);
            $data[$in][$parameter->name] = $item;
        }

        return $data;
    }

    /**
     * @param string|OA\RequestBody $requestBody
     * @return array|<string, <string, RequestBodyDTO>>
     */
    private function parseRequestBody($requestBody): array
    {
        if (Generator::isDefault($requestBody)) {
            return [];
        }

        $data = [];

        foreach ($requestBody->content as $content) {
            $mediaType = $content->mediaType;
            if (Generator::isDefault($content->schema)) {
                continue;
            }

            foreach ($content->schema->properties as $property) {
                if (Generator::isDefault($property->property)) {
                    continue;
                }

                $desc = Generator::isDefault($property->title) ? '' : $property->title;
                if (!Generator::isDefault($property->description)) {
                    $desc = $desc ? $desc . "({$property->description})" : $property->description;
                }
                $item = new RequestBodyDTO([
                    'desc' => $desc,
                    'type' => Generator::isDefault($property->type) ? null : $property->type,
                    'required' => is_array($content->schema->required) && in_array($property->property, $content->schema->required),
                    'nullable' => Generator::isDefault($property->nullable) ? false : $property->nullable,
                    'default' => Generator::isDefault($property->default) ? null : $property->default,
                    'max_length' => Generator::isDefault($property->maxLength) ? null : $property->maxLength,
                    'min_length' => Generator::isDefault($property->minLength) ? null : $property->minLength,
                    'enum' => Generator::isDefault($property->enum) ? null : $property->enum,
                    'min' => Generator::isDefault($property->minimum) ? null : $property->minimum,
                    'max' => Generator::isDefault($property->maximum) ? null : $property->maximum,
                ]);

                $data[$mediaType][$property->property] = $item;
            }
        }

        return $data;
    }
}