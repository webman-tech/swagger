<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

use Illuminate\Support\Str;
use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Response as AnResponse;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Components;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\Swagger\Enums\PropertyInEnum;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * @internal
 */
final class XInPropertyDTO
{
    private const X_KEY = 'in-property';

    public function __construct(
        private readonly PropertyInEnum $in,
        private readonly AnProperty     $property,
        private readonly AnSchema       $schema,
        private ?string                 $refFromPrefix = null,
        private readonly bool           $required = false,
    )
    {
        if ($this->refFromPrefix === null) {
            if (Generator::isDefault($this->schema->schema)) {
                throw new \InvalidArgumentException('schema->schema is required');
            }
            $this->refFromPrefix = Components::ref($schema);
        }
    }

    /**
     * 从 schema 上提出取当前类的列表
     * @return array<string, self>
     */
    public static function getListFromSchema(AnSchema $schema): array
    {
        return SwaggerHelper::getAnnotationXValue($schema, self::X_KEY . '-data', []);
    }

    /**
     * 将 schema 上的当前类信息移除
     */
    public static function removeFromSchema(AnSchema $schema): void
    {
        SwaggerHelper::removeAnnotationXValue($schema, self::X_KEY . '-data');
    }

    /**
     * 将当前类设置到 Schema 上
     */
    public function set2Schema(): void
    {
        $key = self::X_KEY . '-data';
        $values = SwaggerHelper::getAnnotationXValue($this->schema, $key, []);
        $name = $this->property->property;
        if (!isset($values[$name])) {
            $values[$name] = $this;
            SwaggerHelper::setAnnotationXValue($this->schema, $key, $values);
        }
    }

    /**
     * 将相关信息设置到 Operation 上
     */
    public function append2operation(AnOperation $operation, Analysis $analysis): void
    {
        if (in_array($this->in, PropertyInEnum::REQUEST_PARAMETERS, true)) {
            // 转化为 parameter
            $ref = $this->getRefParameter($analysis);
            $parameters = SwaggerHelper::getValue($operation->parameters, []);
            $parameters[] = new Parameter(
                ref: $ref,
            );
            $operation->parameters = $parameters;
        } elseif ($this->in === PropertyInEnum::Body) {
            $mediaType = SwaggerHelper::getOperationRequestBodyMediaType($operation, 'application/octet-stream');
            if (!Generator::isDefault($mediaType->schema)) {
                // body 参数仅能设置一次
                return;
            }
            $mediaSchema = new Schema(
                description: SwaggerHelper::getValue($this->property->description),
                type: 'string',
                format: 'binary',
                nullable: SwaggerHelper::getValue($this->property->nullable),
            );
            $mediaType->schema = $mediaSchema;
        } elseif ($this->in === PropertyInEnum::Json) {
            $mediaType = SwaggerHelper::getOperationRequestBodyMediaType($operation, 'application/json');
            $schema = new Schema(
                properties: [
                    new Property(
                        property: $this->property->property,
                        ref: $this->getRefProperty($analysis),
                    ),
                ],
            );
            SwaggerHelper::appendSchema2mediaType($mediaType, $schema, $analysis);
        }
    }

    /**
     * 将相关信息设置到 Response 上
     */
    public function append2response(AnResponse $response, Analysis $analysis): void
    {
        if ($this->in === PropertyInEnum::Header) {
            // 转化为 header
            // 注意：不使用 $ref，直接定义完整的 header，因为 swagger-php 不支持 $ref 与 required 共存
            $headers = SwaggerHelper::getValue($response->headers, []);
            $header = SwaggerHelper::renewHeaderWithProperty($this->property, $this->required);
            $headers[] = $header;
            $response->headers = $headers;
        } elseif ($this->in === PropertyInEnum::Body) {
            $mediaType = SwaggerHelper::getResponseMediaType($response, 'application/octet-stream');
            if (!Generator::isDefault($mediaType->schema)) {
                // body 参数仅能设置一次
                return;
            }
            $mediaSchema = new Schema(
                description: SwaggerHelper::getValue($this->property->description),
                type: 'string',
                format: 'binary',
                nullable: SwaggerHelper::getValue($this->property->nullable),
            );
            $mediaType->schema = $mediaSchema;
        } elseif ($this->in === PropertyInEnum::Json) {
            $mediaType = SwaggerHelper::getResponseMediaType($response, 'application/json');
            $schema = new Schema(
                properties: [
                    new Property(
                        property: $this->property->property,
                        ref: $this->getRefProperty($analysis),
                    ),
                ],
            );
            SwaggerHelper::appendSchema2mediaType($mediaType, $schema, $analysis);
        }
    }

    public function getIn(): PropertyInEnum
    {
        return $this->in;
    }

    private function getRefParameter(Analysis $analysis): string
    {
        $key = self::X_KEY . '-parameter';
        $values = SwaggerHelper::getAnnotationXValue($this->schema, $key, []);
        $name = $this->getSchemaBasedName($this->property->property);
        if (!isset($values[$name])) {
            $parameter = SwaggerHelper::renewParameterWithProperty(
                property: $this->property,
                in: $this->in->toParameterIn(),
                required: $this->required,
                isForParameterRef: true,
            );
            $parameter->parameter = $name; // 该参数确保了在 components 中的 name
            $ref = SwaggerHelper::appendComponent($analysis, $parameter);

            $values[$name] = $ref;
            SwaggerHelper::setAnnotationXValue($this->schema, $key, $values);
        }
        return $values[$name];
    }

    private function getRefProperty(Analysis $analysis): string
    {
        foreach (SwaggerHelper::getValue($this->schema->properties, []) as $property) {
            $name = $this->property->property;
            /** @var AnProperty $property */
            if ($property->property === $name) {
                // property 还在原 schema 中 → 原 schema 必有根级引用，子路径引用安全
                return "{$this->refFromPrefix}/properties/$name";
            }
        }

        // property 已被移走 → 提升为独立 Schema
        $key = self::X_KEY . '-property';
        $values = SwaggerHelper::getAnnotationXValue($this->schema, $key, []);
        $name = $this->getSchemaBasedName($this->property->property);
        if (!isset($values[$name])) {
            $schema = SwaggerHelper::renewSchemaWithProperty($this->property);
            $schema->schema = $name; // 该参数确保了在 components 中的 name
            $ref = SwaggerHelper::appendComponent($analysis, $schema);

            $values[$name] = $ref . '/properties/' . $this->property->property;
            SwaggerHelper::setAnnotationXValue($this->schema, $key, $values);
        }
        return $values[$name];
    }

    private function getSchemaBasedName(string $name): string
    {
        $schemaName = $this->schema->schema;
        if (Generator::isDefault($schemaName)) {
            // 虽然 construct 中对此做了检查，但可能存在初始化后，schema 被更改的可能
            $schemaName = Str::of(SwaggerHelper::getAnnotationClassName($this->schema))
                ->replace('\\', '')
                ->__toString();
        }
        return $schemaName . '_' . $name;
    }
}
