<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Header as AnHeader;
use OpenApi\Annotations\MediaType as AnMediaType;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Response as AnResponse;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Components;
use OpenApi\Attributes\Header;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * 将定义的 schema 转为 response 上
 */
final class XSchemaResponseProcessor
{
    public const REF = SchemaConstants::X_SCHEMA_RESPONSE;

    private Analysis $analysis;

    public function __invoke(Analysis $analysis): void
    {
        $this->analysis = $analysis;
        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);

        foreach ($operations as $operation) {
            if (!Generator::isDefault($operation->x) && array_key_exists(self::REF, $operation->x)) {
                $value = $this->normalizeSchemaArray($operation->x[self::REF]);

                foreach ($value as $statusCode => $classList) {
                    foreach ($classList as $class) {
                        $schema = $analysis->getSchemaForSource($class);
                        if (!$schema instanceof AnSchema) {
                            throw new \InvalidArgumentException(sprintf('Value of `x.%s.%s` must be a schema reference', self::REF, $class));
                        }

                        $this->expand($operation, $statusCode, $schema);
                    }
                }

                $this->cleanUp($operation);
            }
        }
    }

    private function normalizeSchemaArray($value): array
    {
        if (is_string($value)) {
            // 单 string
            $value = [$value];
        }
        if (isset($value[0])) {
            // index 数组
            $value = [200 => $value];
        }
        return array_map(function ($itemValue) {
            if (is_string($itemValue)) {
                return [$itemValue];
            }
            return $itemValue;
        }, $value);
    }

    private function expand(AnOperation $operation, int $statusCode, AnSchema $schema): void
    {
        if (!Generator::isDefault($schema->allOf)) {
            // support allOf
            foreach ($schema->allOf as $itemSchema) {
                $this->expand($operation, $statusCode, $itemSchema);
            }
        }

        if (!Generator::isDefault($schema->ref)) {
            // support ref
            $refSchema = $this->analysis->openapi?->ref((string)$schema->ref);
            if (!$refSchema instanceof AnSchema) {
                throw new \InvalidArgumentException('ref must be a schema reference');
            }
            $this->expand($operation, $statusCode, $refSchema);
        }

        if (Generator::isDefault($schema->properties) || !$schema->properties) {
            return;
        }

        // 处理 x in
        $propertiesIn = new \WeakMap();
        foreach ($schema->properties as $property) {
            // 处理 x in
            $propertyIn = SwaggerHelper::getPropertyXValue($property, SchemaConstants::X_PROPERTY_IN);
            // 获取默认的 in 的位置
            if ($propertyIn === null) {
                $propertyIn = SchemaConstants::X_PROPERTY_IN_JSON;
            }
            $propertiesIn[$property] = $propertyIn;
        }
        // 检查所有 schema 的参数是否都在 json 中
        $isAllPropertiesInJson = true;
        foreach ($schema->properties as $property) {
            if ($propertiesIn[$property] !== SchemaConstants::X_PROPERTY_IN_JSON) {
                $isAllPropertiesInJson = false;
                break;
            }
        }

        $response = $this->getResponse($operation, $statusCode);

        if ($isAllPropertiesInJson) {
            // 全部都在 json 中的，直接附加到 ref 上
            $this->add2responseBodyJsonUseRef($response, $schema);
        } else {
            // 根据 propertyIn 一个去处理
            $schemaRequired = SwaggerHelper::getValue($schema->required, []);

            foreach ($schema->properties as $property) {
                $propertyIn = $propertiesIn[$property];

                $isRequired = in_array($property->property, $schemaRequired, true);
                $isNullable = SwaggerHelper::getValue($property->nullable);
                $description = SwaggerHelper::getValue($property->description);

                if ($propertyIn === SchemaConstants::X_PROPERTY_IN_HEADER) {
                    // 转为 Header
                    $schemaNew = SwaggerHelper::renewSchemaWithProperty($property);
                    $schemaNew->_context = $operation->_context; // inherit context from operation, required to pretend to be a parameter

                    $header = new Header(
                        header: $property->property,
                        description: $description,
                        required: $isRequired,
                    );
                    $header->schema = $schemaNew;
                    $header->_context = $operation->_context; // inherit context from operation, required to pretend to be a parameter

                    $this->add2headers($response, $header);
                } elseif ($propertyIn === SchemaConstants::X_PROPERTY_IN_JSON) {
                    // 转为 JsonBody
                    $schemaNew = $this->add2responseBodyJson($response, $property);
                    if ($isRequired) {
                        if (Generator::isDefault($schemaNew->required)) {
                            $schemaNew->required = [];
                        }
                        $schemaNew->required[] = $property->property;
                    }
                } elseif ($propertyIn === SchemaConstants::X_PROPERTY_IN_BODY) {
                    // 转为 Body
                    $schemaNew = new Schema(
                        description: $description,
                        type: 'string',
                        format: 'binary',
                        nullable: $isNullable,
                    );

                    $mediaType = $this->getResponseMediaType($response, 'application/octect-stream');
                    $mediaType->schema = $schemaNew;
                } else {
                    throw new \InvalidArgumentException(sprintf('Not support [%s] in `x.in`, class: [%s], property: [%s]', $propertyIn, $property->_context->class, $property->property));
                }
            }
        }
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

    private function getResponseMediaType(AnResponse $response, string $mediaType): AnMediaType
    {
        if (Generator::isDefault($response->content)) {
            $response->content = [];
        }
        if (!isset($response->content[$mediaType])) {
            $response->content[$mediaType] = new MediaType(
                mediaType: $mediaType,
            );
        }
        return $response->content[$mediaType];
    }

    private function add2headers(AnResponse $response, AnHeader $header): void
    {
        if (Generator::isDefault($response->headers)) {
            $response->headers = [];
        }
        $response->headers[] = $header;
    }

    private function add2responseBodyJson(AnResponse $response, AnProperty $property): AnSchema
    {
        $mediaType = $this->getResponseMediaType($response, 'application/json');
        if (Generator::isDefault($mediaType->schema)) {
            $mediaType->schema = new Schema();
        }
        $schema = $mediaType->schema;
        if (Generator::isDefault($schema->properties)) {
            $schema->properties = [];
        }
        $schema->properties[] = $property;

        return $schema;
    }

    private function add2responseBodyJsonUseRef(AnResponse $response, AnSchema $schema): void
    {
        $mediaType = $this->getResponseMediaType($response, 'application/json');
        if (Generator::isDefault($mediaType->schema)) {
            $mediaType->schema = new Schema();
        }
        if (Generator::isDefault($mediaType->schema->ref)) {
            $mediaType->schema->ref = Components::ref($schema);
        } else {
            if (Generator::isDefault($mediaType->schema->allOf)) {
                $mediaType->schema->allOf = [
                    clone $mediaType->schema,
                ];
                $mediaType->schema->ref = Generator::UNDEFINED;
            }
            $mediaType->schema->allOf[] = new Schema(ref: Components::ref($schema));
        }
    }

    private function cleanUp(AnOperation $operation): void
    {
        unset($operation->x[self::REF]);
        if (!$operation->x) {
            /* @phpstan-ignore-next-line */
            $operation->x = Generator::UNDEFINED;
        }
    }
}
