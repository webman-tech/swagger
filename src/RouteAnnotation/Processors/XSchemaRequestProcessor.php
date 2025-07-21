<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Context;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Enums\PropertyInEnum;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation\DTO\XInPropertyDTO;

/**
 * 将定义的 schema 转到 request 的 parameters 或 requestBody 上
 */
final class XSchemaRequestProcessor
{
    private const X_SCHEMA = SchemaConstants::X_SCHEMA_REQUEST;
    private const X_SCHEMA_CLASS_METHOD = '_temp-class-method';

    private Analysis $analysis;

    public function __invoke(Analysis $analysis): void
    {
        $this->analysis = $analysis;
        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);

        foreach ($operations as $operation) {
            $schemaList = $this->getNormalizedSchemaValues($operation);
            if ($schemaList === null) {
                continue;
            }
            $defaultPropertyIn = match ($operation->method) {
                'get', 'head', 'options' => PropertyInEnum::Query,
                default => PropertyInEnum::Json,
            };
            foreach ($schemaList as $schema) {
                // 根据 property in 所在的地方，将数据转移到不同上面
                $propertyIn = PropertyInEnum::tryFromSchemaX($schema, $defaultPropertyIn);
                // 将 schema 上的 x-in-property 放到对应位置
                $this->addXInProperties($operation, $schema);
                if ($propertyIn === PropertyInEnum::Json) {
                    // 添加到 requestBody 上
                    $this->add2requestBodyJsonUseRef($operation, $schema);
                } elseif (in_array($propertyIn, PropertyInEnum::REQUEST_PARAMETERS, true)) {
                    // 添加到 parameters 上
                    $this->add2parametersUseSchema($operation, $schema, $propertyIn);
                }

                // 从 classMethod 上提取出 response 类型
                if ($classMethod = SwaggerHelper::getAnnotationXValue($schema, self::X_SCHEMA_CLASS_METHOD)) {
                    SwaggerHelper::removeAnnotationXValue($schema, self::X_SCHEMA_CLASS_METHOD);
                    $this->add2responseXSchemaResponse($operation, $classMethod);
                }
            }

            // 清理
            SwaggerHelper::removeAnnotationXValue($operation, self::X_SCHEMA);
        }
    }

    /**
     * @return AnSchema[]|null
     */
    private function getNormalizedSchemaValues(AnOperation $operation): ?array
    {
        $schemaList = SwaggerHelper::getAnnotationXValue($operation, self::X_SCHEMA);
        if ($schemaList === null) {
            return null;
        }
        if (is_string($schemaList) || $schemaList instanceof AnSchema) {
            $schemaList = [$schemaList];
        }
        if (!is_array($schemaList)) {
            throw new \InvalidArgumentException(sprintf('operation path %s, value of `x.%s` type error', $operation->path, self::X_SCHEMA));
        }
        return array_map(function ($schema) use ($operation): AnSchema {
            if (is_string($schema)) {
                // 字符串的形式
                $class = $schema;
                $classMethod = null;
                if (str_contains($schema, '@')) {
                    // $class@method 的形式
                    [$class] = $classMethod = explode('@', $schema);
                }
                $schema = $this->analysis->getSchemaForSource($class);
                if (!$schema instanceof AnSchema) {
                    throw new \InvalidArgumentException(sprintf('Class `%s` not exists, in operation path %s', $class, $operation->path));
                }
                if ($classMethod) {
                    SwaggerHelper::setAnnotationXValue($schema, self::X_SCHEMA_CLASS_METHOD, $classMethod);
                }
            }
            if (!$schema instanceof AnSchema) {
                throw new \InvalidArgumentException(sprintf('operation path %s, value of `x.%s` type error', $operation->path, self::X_SCHEMA));
            }
            return $schema;
        }, $schemaList);
    }

    private function addXInProperties(AnOperation $operation, AnSchema $schema): void
    {
        // allOf 的逐个处理掉
        if ($allOf = SwaggerHelper::getValue($schema->allOf)) {
            foreach ($allOf as $allOfItem) {
                $this->addXInProperties($operation, $allOfItem);
            }
        }
        // schema 是 ref 的情况下，取到真实的 schema
        if (!Generator::isDefault($schema->ref)) {
            $schema = $this->analysis->getSchemaForSource(SwaggerHelper::getAnnotationClassName($schema));
        }
        if (!$schema) {
            return;
        }
        // 添加到 operation 上
        $xInProperties = XInPropertyDTO::getListFromSchema($schema);
        foreach ($xInProperties as $xInProperty) {
            $xInProperty->append2operation($operation, $this->analysis);
        }
    }

    private function add2requestBodyJsonUseRef(AnOperation $operation, AnSchema $schema): void
    {
        $mediaType = SwaggerHelper::getOperationRequestBodyMediaType($operation, 'application/json');
        SwaggerHelper::appendSchema2mediaType($mediaType, $schema, $this->analysis);
    }

    private function add2parametersUseSchema(AnOperation $operation, AnSchema $schema, PropertyInEnum $propertyIn): void
    {
        $parameters = array_merge(
            SwaggerHelper::getValue($operation->parameters, []),
            $this->transferSchemaProperties2parameters($schema, $operation->_context, $propertyIn),
        );
        $operation->parameters = $parameters;
    }

    private function transferSchemaProperties2parameters(AnSchema $schema, Context $context, PropertyInEnum $propertyIn): array
    {
        $parameters = [];
        // allOf 的逐个转化
        if ($allOf = SwaggerHelper::getValue($schema->allOf)) {
            foreach ($allOf as $allOfItem) {
                $parameters = array_merge(
                    $parameters,
                    $this->transferSchemaProperties2parameters($allOfItem, $context, $propertyIn),
                );
            }
        }
        // schema 是 ref 的情况下，取到真实的 schema
        if (!Generator::isDefault($schema->ref)) {
            $schema = $this->analysis->getSchemaForSource(SwaggerHelper::getAnnotationClassName($schema));
            if ($schema) {
                $parameters = $this->transferSchemaProperties2parameters($schema, $context, $propertyIn);
            }
            return $parameters;
        }

        // properties 的逐个转化
        $schemaRequired = SwaggerHelper::getValue($schema->required, []);
        foreach (SwaggerHelper::getValue($schema->properties, []) as $property) {
            /** @var AnProperty $property */
            $parameters[] = SwaggerHelper::renewParameterWithProperty(
                property: $property,
                in: $propertyIn->toParameterIn(),
                required: in_array($property->property, $schemaRequired, true),
            );
        }

        return $parameters;
    }

    private function add2responseXSchemaResponse(AnOperation $operation, array $classMethod): void
    {
        $value = SwaggerHelper::getAnnotationXValue($operation, SchemaConstants::X_SCHEMA_RESPONSE);
        if ($value) {
            // 如果已经定义过，则不覆盖
            return;
        }

        [$class, $method] = $classMethod;
        $reflectionClass = new \ReflectionClass($class);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $reflectionReturnType = $reflectionMethod->getReturnType();
        if (!$reflectionReturnType instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException("{$class}@{$method} 必须定义返回类型，且唯一");
        }

        SwaggerHelper::setAnnotationXValue($operation, SchemaConstants::X_SCHEMA_RESPONSE, $reflectionReturnType->getName());
    }
}
