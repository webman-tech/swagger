<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Header as AnHeader;
use OpenApi\Annotations\MediaType as AnMediaType;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Response as AnResponse;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Header;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * 将定义的 schema 转为 response 上
 */
final class SchemaResponse
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
                $value = $operation->x[self::REF];
                if (is_string($value)) {
                    $value = [200 => $value];
                }
                if (!is_array($value)) {
                    throw new \InvalidArgumentException(sprintf('Value of `x.%s` must be a string or array of strings', self::REF));
                }

                foreach ($value as $statusCode => $class) {
                    $schema = $analysis->getSchemaForSource($class);
                    if (!$schema instanceof AnSchema) {
                        throw new \InvalidArgumentException(sprintf('Value of `x.%s.%s` must be a schema reference', self::REF, $class));
                    }

                    $this->exapand($operation, $statusCode, $schema);
                }

                $this->cleanUp($operation);
            }
        }
    }

    private function exapand(AnOperation $operation, int $statusCode, AnSchema $schema): void
    {
        if (!Generator::isDefault($schema->allOf)) {
            // support allOf
            foreach ($schema->allOf as $itemSchema) {
                $this->exapand($operation, $statusCode, $itemSchema);
            }
        }

        if (!Generator::isDefault($schema->ref)) {
            // support ref
            $refSchema = $this->analysis->openapi?->ref((string)$schema->ref);
            if (!$refSchema instanceof AnSchema) {
                throw new \InvalidArgumentException('ref must be a schema reference');
            }
            $this->exapand($operation, $statusCode, $refSchema);
        }

        if (Generator::isDefault($schema->properties) || !$schema->properties) {
            return;
        }

        $response = $this->getResponse($operation, $statusCode);
        $schemaRequired = Generator::isDefault($schema->required) ? [] : $schema->required;

        foreach ($schema->properties as $property) {
            $propertyIn = null;
            $propertyRequired = null;
            if (!Generator::isDefault($property->x)) {
                if (array_key_exists(SchemaConstants::X_PROPERTY_IN, $property->x)) {
                    $propertyIn = (string)$property->x[SchemaConstants::X_PROPERTY_IN];
                    //unset($property->x[SchemaConstants::X_PROPERTY_IN]); // 不能清理，有些基础类会复用
                }
                if (array_key_exists(SchemaConstants::X_PROPERTY_REQUIRED, $property->x)) {
                    $propertyRequired = (bool)$property->x[SchemaConstants::X_PROPERTY_REQUIRED];
                    //unset($property->x[SchemaConstants::X_PROPERTY_REQUIRED]); // 不能清理，有些基础类会复用
                }
                if (!$property->x) {
                    $property->x = Generator::UNDEFINED;
                }
            }

            if ($propertyIn === null) {
                $propertyIn = SchemaConstants::X_PROPERTY_IN_JSON;
            }

            if ($propertyRequired !== null) {
                // 根据 property 上定义的 x.required ，补全或者提出掉 schema 上的 required
                $isInSchemaRequired = in_array($property->property, $schemaRequired, true);
                if ($propertyRequired && !$isInSchemaRequired) {
                    $schemaRequired[] = $property->property;
                }
                if (!$propertyRequired && $isInSchemaRequired) {
                    $schemaRequired = array_filter($schemaRequired, fn($item) => $item !== $property->property);
                }
            }

            $isRequired = in_array($property->property, $schemaRequired, true);
            $isNullable = Generator::isDefault($property->nullable) ? null : $property->nullable;
            $description = Generator::isDefault($property->description) ? null : $property->description;

            if ($propertyIn === SchemaConstants::X_PROPERTY_IN_HEADER) {
                $schemaNew = new Schema(
                    type: Generator::isDefault($property->format) ? $property->type : $property->format,
                    example: $property->example,
                    nullable: $isNullable,
                );

                $header = new Header(
                    header: $property->property,
                    description: $description,
                    required: !$isNullable,
                    schema: $schemaNew,
                );

                $this->add2headers($response, $header);
            } elseif ($propertyIn === SchemaConstants::X_PROPERTY_IN_JSON) {
                $schemaNew = $this->add2responseBodyJson($response, $property);
                if ($isRequired) {
                    if (Generator::isDefault($schemaNew->required)) {
                        $schemaNew->required = [];
                    }
                    $schemaNew->required[] = $property->property;
                }
            } elseif ($propertyIn === SchemaConstants::X_PROPERTY_IN_BODY) {
                $schema = new Schema(
                    description: $description,
                    type: 'string',
                    format: 'binary',
                    nullable: $isNullable,
                );

                $mediaType = $this->getResponseMediaType($response, 'application/octect-stream');
                $mediaType->schema = $schema;
            } else {
                throw new \InvalidArgumentException(sprintf('Not support [%s] in `x.in`, class: [%s], property: [%s]', $propertyIn, $property->_context->class, $property->property));
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

    private function cleanUp(AnOperation $operation): void
    {
        unset($operation->x[self::REF]);
        if (!$operation->x) {
            /* @phpstan-ignore-next-line */
            $operation->x = Generator::UNDEFINED;
        }
    }
}
