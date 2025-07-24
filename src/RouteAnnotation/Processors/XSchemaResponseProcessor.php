<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Response as AnResponse;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Header;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use OpenApi\Context;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Enums\PropertyInEnum;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation\DTO\XInPropertyDTO;

/**
 * 将定义的 schema 转为 response 上
 */
final class XSchemaResponseProcessor
{
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
            $propertyDefaultIn = PropertyInEnum::Json;
            foreach ($schemaList as $statusCode => $schemas) {
                $response = $this->getResponse($operation, $statusCode);
                foreach ($schemas as $schema) {
                    // 根据 property in 所在的地方，将数据转移到不同上面
                    $propertyIn = PropertyInEnum::tryFromSchemaX($schema, $propertyDefaultIn);
                    // 将 schema 上的 x-in-property 放到对应位置
                    $this->addXInProperties($response, $schema);
                    if ($propertyIn === PropertyInEnum::Json) {
                        // json 的添加到 requestBody 上
                        $this->add2responseBodyJsonUseRef($response, $schema);
                    } elseif ($propertyIn === PropertyInEnum::Header) {
                        // header
                        $this->add2responseHeadersUseSchema($response, $schema);
                    } elseif ($propertyIn === PropertyInEnum::Body) {
                        // body 的
                        $this->add2responseBodyUseSchema($response, $schema);
                    }
                }
            }
        }
    }

    /**
     * @return array<int, AnSchema[]>|null
     */
    private function getNormalizedSchemaValues(AnOperation $operation): ?array
    {
        $schemaList = SwaggerHelper::getAnnotationXValue($operation, SchemaConstants::X_SCHEMA_RESPONSE, remove: true);
        if ($schemaList === null) {
            return null;
        }
        if (is_string($schemaList) || $schemaList instanceof AnSchema) {
            // 单 string 或 Schema
            $schemaList = [$schemaList];
        }
        if (!is_array($schemaList)) {
            throw new \InvalidArgumentException(sprintf('operation path %s, value of `x.%s` type error', $operation->path, SchemaConstants::X_SCHEMA_RESPONSE));
        }
        if (isset($schemaList[0])) {
            // index 数组
            $schemaList = [200 => $schemaList];
        }
        // 将所有 code => [] 或 code => '' 形式的转为 code => []
        $schemaList = array_map(function ($item) use ($operation): array {
            if (is_string($item) || $item instanceof AnSchema) {
                $item = [$item];
            }
            if (!is_array($item)) {
                throw new \InvalidArgumentException(sprintf('operation path %s, value of `x.%s` type error', $operation->path, SchemaConstants::X_SCHEMA_RESPONSE));
            }
            return $item;
        }, $schemaList);
        // 将所有的子项 string 转为 Schema
        return array_map(fn($schemaList): array => array_map(function ($schema) use ($operation): AnSchema {
            if (is_string($schema)) {
                // 字符串的形式
                if (class_exists($schema) || trait_exists($schema) || interface_exists($schema)) {
                    $class = $schema;
                    $schema = $this->analysis->getSchemaForSource($class);
                    if (!$schema instanceof AnSchema) {
                        throw new \InvalidArgumentException(sprintf('Class `%s` not exists, in %s', $class, $operation->_context));
                    }
                } else {
                    $schema = new Schema(
                        description: $schema,
                    );
                }
            }
            if (!$schema instanceof AnSchema) {
                throw new \InvalidArgumentException(sprintf('operation path %s, value of `x.%s` type error', $operation->path, SchemaConstants::X_SCHEMA_RESPONSE));
            }
            return $schema;
        }, $schemaList), $schemaList);
    }

    private function getResponse(AnOperation $operation, int $statusCode): AnResponse
    {
        if (Generator::isDefault($operation->responses)) {
            $operation->responses = [];
        }
        if (!isset($operation->responses[$statusCode])) {
            $operation->responses[$statusCode] = new Response(
                response: $statusCode,
                description: 'OK',
            );
        }
        return $operation->responses[$statusCode];
    }

    private function addXInProperties(AnResponse $response, AnSchema $schema): void
    {
        // allOf 的逐个处理掉
        if ($allOf = SwaggerHelper::getValue($schema->allOf)) {
            foreach ($allOf as $allOfItem) {
                $this->addXInProperties($response, $allOfItem);
            }
        }
        // schema 是 ref 的情况下，取到真实的 schema
        if (!Generator::isDefault($schema->ref)) {
            $schema = $this->analysis->getSchemaForSource($schema->_context->fullyQualifiedName($schema->_context->class));
        }
        if (!$schema) {
            return;
        }
        // 添加到 response 上
        $xInProperties = XInPropertyDTO::getListFromSchema($schema);
        foreach ($xInProperties as $xInProperty) {
            $xInProperty->append2response($response, $this->analysis);
        }
    }

    private function add2responseBodyJsonUseRef(AnResponse $response, AnSchema $schema): void
    {
        $mediaType = SwaggerHelper::getResponseMediaType($response, 'application/json');
        SwaggerHelper::appendSchema2mediaType($mediaType, $schema, $this->analysis);
    }

    private function add2responseHeadersUseSchema(AnResponse $response, AnSchema $schema): void
    {
        $headers = array_merge(
            SwaggerHelper::getValue($response->headers, []),
            $this->transferSchemaProperties2headers($schema, $response->_context),
        );
        $response->headers = $headers;
    }

    private function transferSchemaProperties2headers(AnSchema $schema, Context $context): array
    {
        $headers = [];
        if ($allOf = SwaggerHelper::getValue($schema->allOf)) {
            foreach ($allOf as $allOfItem) {
                $headers = array_merge($headers, $this->transferSchemaProperties2headers($allOfItem, $context));
            }
        } elseif (SwaggerHelper::getValue($schema->oneOf)) {
            // 不支持存在 oneOf 的转为 headers
            throw new \InvalidArgumentException('Not support use oneOf');
        }

        $schemaRequired = SwaggerHelper::getValue($schema->required, []);
        foreach (SwaggerHelper::getValue($schema->properties, []) as $property) {
            /** @var AnProperty $property */
            $headerSchema = SwaggerHelper::renewSchemaWithProperty($property);
            $headerSchema->_context = $context;

            $header = new Header(
                header: $property->property,
                description: SwaggerHelper::getValue($property->description),
                required: in_array($property->property, $schemaRequired, true),
            );
            $header->schema = $headerSchema;
            $header->_context = $context;
            $headers[] = $header;
        }

        return $headers;
    }

    private function add2responseBodyUseSchema(AnResponse $response, AnSchema $schema): void
    {
        $mediaSchema = new Schema(
            description: SwaggerHelper::getValue($schema->description),
            type: 'string',
            format: 'binary',
            nullable: SwaggerHelper::getValue($schema->nullable),
        );

        $mediaType = SwaggerHelper::getResponseMediaType($response, 'application/octect-stream');
        $mediaType->schema = $mediaSchema;
    }
}
