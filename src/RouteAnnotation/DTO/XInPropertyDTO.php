<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Response as AnResponse;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Components;
use OpenApi\Attributes\Header;
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
            if (!$this->schema->schema) {
                throw new \InvalidArgumentException('schema->schema is required');
            }
            $this->refFromPrefix = Components::ref($schema);
        }
        if (str_contains($this->refFromPrefix, 'Generator')) {
            throw new \InvalidArgumentException('schema->schema is not valid');
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
            $ref = $this->getRefParameter();
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
                        ref: $this->getRefProperty(),
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
            $ref = $this->getRefHeader();
            $headers = SwaggerHelper::getValue($response->headers, []);
            $headers[] = new Header(
                ref: $ref,
                header: $this->property->property,
            );
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
                        ref: $this->getRefProperty(),
                    ),
                ],
            );
            SwaggerHelper::appendSchema2mediaType($mediaType, $schema, $analysis);
        }
    }

    private function getRefParameter(): string
    {
        $key = self::X_KEY . '-parameter';
        $values = SwaggerHelper::getAnnotationXValue($this->schema, $key, []);
        $name = $this->property->property;
        if (!isset($values[$name])) {
            $values[$name] = SwaggerHelper::renewParameterWithProperty(
                property: $this->property,
                in: $this->in->toParameterIn(),
                required: $this->required,
                isForParameterRef: true,
            );;
            // 按需使用时将参数添加到 schema 的 x-xx 中
            SwaggerHelper::setAnnotationXValue($this->schema, $key, $values);
        }
        // 返回 ref
        return "{$this->refFromPrefix}/x-{$key}/$name";
    }

    private function getRefProperty(): string
    {
        $name = $this->property->property;
        foreach (SwaggerHelper::getValue($this->schema->properties, []) as $property) {
            /** @var AnProperty $property */
            if ($property->property === $name) {
                // property 在原 schema 还存在的情况
                return "{$this->refFromPrefix}/properties/$name";
            }
        }
        // property 已经被挪走的情况
        $key = self::X_KEY . '-property';
        $values = SwaggerHelper::getAnnotationXValue($this->schema, $key, []);
        if (!isset($values[$name])) {
            $values[$name] = $this->property;
            // 按需使用时将参数添加到 schema 的 x-xx 中
            SwaggerHelper::setAnnotationXValue($this->schema, $key, $values);
        }
        // 返回 ref
        return "{$this->refFromPrefix}/x-{$key}/$name";
    }

    private function getRefHeader(): string
    {
        $key = self::X_KEY . '-header';
        $values = SwaggerHelper::getAnnotationXValue($this->schema, $key, []);
        $name = $this->property->property;
        if (!isset($values[$name])) {
            $values[$name] = SwaggerHelper::renewHeaderWithProperty(
                property: $this->property,
                required: $this->required,
            );;
            // 按需使用时将参数添加到 schema 的 x-xx 中
            SwaggerHelper::setAnnotationXValue($this->schema, $key, $values);
        }
        // 返回 ref
        return "{$this->refFromPrefix}/x-{$key}/$name";
    }
}
