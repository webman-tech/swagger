<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\MediaType as AnMediaType;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Parameter as AnParameter;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * 将定义的 schema 转到 request 的 parameters 或 requestBody 上
 */
final class SchemaRequest
{
    private const REF = SchemaConstants::X_SCHEMA_REQUEST;

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
                    $value = [$value];
                }
                if (!is_array($value)) {
                    throw new \InvalidArgumentException(sprintf('Value of `x.%s` must be a string or array of strings', self::REF));
                }
                foreach ($value as $classWithMethod) {
                    $class = $classWithMethod;
                    $method = null;
                    if (str_contains($classWithMethod, '@')) {
                        [$class, $method] = explode('@', $classWithMethod);
                    }
                    $schema = $analysis->getSchemaForSource($class);
                    if (!$schema instanceof AnSchema) {
                        throw new \InvalidArgumentException(sprintf('Value of `x.%s.%s` must be a schema reference', self::REF, $class));
                    }

                    $this->exapand($operation, $schema);

                    if ($method) {
                        $this->add2responseXSchemaResponse($operation, $class, $method);
                    }
                }

                $this->cleanUp($operation);
            }
        }
    }

    private function exapand(AnOperation $operation, AnSchema $schema): void
    {
        if (!Generator::isDefault($schema->allOf)) {
            // support allOf
            foreach ($schema->allOf as $itemSchema) {
                $this->exapand($operation, $itemSchema);
            }
        }

        if (!Generator::isDefault($schema->ref)) {
            // support ref
            $refSchema = $this->analysis->openapi?->ref((string)$schema->ref);
            if (!$refSchema instanceof AnSchema) {
                throw new \InvalidArgumentException('ref must be a schema reference');
            }
            $this->exapand($operation, $refSchema);
        }

        if (Generator::isDefault($schema->properties) || !$schema->properties) {
            return;
        }

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
                $propertyIn = match ($operation->method) {
                    'get', 'head', 'options' => SchemaConstants::X_PROPERTY_IN_QUERY,
                    default => SchemaConstants::X_PROPERTY_IN_JSON,
                };
            }
            if ($propertyIn === SchemaConstants::X_PROPERTY_IN_GET) {
                $propertyIn = SchemaConstants::X_PROPERTY_IN_QUERY;
            } elseif ($propertyIn === SchemaConstants::X_PROPERTY_IN_POST) {
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

            if (in_array($propertyIn, [
                SchemaConstants::X_PROPERTY_IN_COOKIE,
                SchemaConstants::X_PROPERTY_IN_HEADER,
                SchemaConstants::X_PROPERTY_IN_PATH,
                SchemaConstants::X_PROPERTY_IN_QUERY,
            ], true)) {
                $schemaNew = new Schema(
                    type: Generator::isDefault($property->format) ? $property->type : $property->format,
                    nullable: $isNullable,
                );
                $schemaNew->_context = $operation->_context; // inherit context from operation, required to pretend to be a parameter

                $parameter = new Parameter(
                    name: $property->property,
                    description: $description,
                    in: $propertyIn,
                    required: $isRequired,
                    schema: $schemaNew,
                    example: $property->example,
                );
                $parameter->_context = $operation->_context; // inherit context from operation, required to pretend to be a parameter

                $this->add2parameters($operation, $parameter);
            } elseif ($propertyIn === SchemaConstants::X_PROPERTY_IN_JSON) {
                $schemaNew = $this->add2requestBodyJson($operation, $property);
                if ($isRequired) {
                    if (Generator::isDefault($schemaNew->required)) {
                        $schemaNew->required = [];
                    }
                    $schemaNew->required[] = $property->property;
                }
            } elseif ($propertyIn === SchemaConstants::X_PROPERTY_IN_BODY) {
                $schemaNew = new Schema(
                    description: $description,
                    type: 'string',
                    format: 'binary',
                    nullable: $isNullable,
                );

                $mediaType = $this->getRequestMediaType($operation, 'application/octect-stream');
                $mediaType->schema = $schemaNew;
            } else {
                throw new \InvalidArgumentException(sprintf('Not support [%s] in `x.in`, class: [%s], property: [%s]', $propertyIn, $property->_context->class, $property->property));
            }

            // 将重新修改过的 required 信息补全到原 schema 上
            $schema->required = empty($schemaRequired) ? Generator::UNDEFINED : $schemaRequired;
        }
    }

    private function add2parameters(AnOperation $operation, AnParameter $parameter): void
    {
        if (Generator::isDefault($operation->parameters)) {
            $operation->parameters = [];
        }
        $operation->parameters[] = $parameter;
    }

    private function getRequestMediaType(AnOperation $operation, string $mediaType): AnMediaType
    {
        if (Generator::isDefault($operation->requestBody)) {
            $operation->requestBody = new RequestBody();
        }
        if (Generator::isDefault($operation->requestBody->content)) {
            $operation->requestBody->content = [];
        }
        if (!isset($operation->requestBody->content[$mediaType])) {
            $operation->requestBody->content[$mediaType] = new MediaType(
                mediaType: $mediaType,
            );
        }
        return $operation->requestBody->content[$mediaType];
    }

    private function add2requestBodyJson(AnOperation $operation, AnProperty $property): AnSchema
    {
        $mediaType = $this->getRequestMediaType($operation, 'application/json');
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

    private function add2responseXSchemaResponse(AnOperation $operation, string $class, string $method): void
    {
        if (Generator::isDefault($operation->x)) {
            $operation->x = [];
        }
        $responseXSchemaResponseKey = SchemaResponse::REF;
        if (isset($operation->x[$responseXSchemaResponseKey])) {
            // 如果已经定义过，则不覆盖
            return;
        }

        $reflectionClass = new \ReflectionClass($class);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $reflectionReturnType = $reflectionMethod->getReturnType();
        if (!$reflectionReturnType instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException("{$class}@{$method} 必须定义返回类型，且唯一");
        }

        $operation->x[$responseXSchemaResponseKey][200] = '\\' . $reflectionReturnType->getName();
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
