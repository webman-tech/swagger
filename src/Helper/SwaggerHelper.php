<?php

namespace WebmanTech\Swagger\Helper;

use OpenApi\Analysis;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\Header as AnHeader;
use OpenApi\Annotations\MediaType as AnMediaType;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Parameter as AnParameter;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Response as AnResponse;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Components;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Webman\Http\UploadFile as WebmanUploadedFile;

/**
 * @internal
 */
final class SwaggerHelper
{
    /**
     * 获取值
     * @template T
     * @template K of mixed
     * @param T $value
     * @param K $default
     * @return T|K
     */
    public static function getValue(mixed $value, mixed $default = null): mixed
    {
        return Generator::isDefault($value) ? $default : $value;
    }

    /**
     * @template T of mixed
     * @param T $key
     * @param T $value
     */
    public static function setValue(mixed &$key, mixed $value): void
    {
        /** @phpstan-ignore-next-line */
        $key = $value ?: Generator::UNDEFINED;
    }

    /**
     * 获取 注解 的 class 的名称
     */
    public static function getAnnotationClassName(AbstractAnnotation $annotation): string
    {
        if ($annotation->_context->is('class')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->class);
        } elseif ($annotation->_context->is('interface')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->interface);
        } elseif ($annotation->_context->is('trait')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->trait);
        } elseif ($annotation->_context->is('enum')) {
            $className = $annotation->_context->fullyQualifiedName($annotation->_context->enum);
        } else {
            $className = $annotation->_context->fullyQualifiedName(
                $annotation->_context->class
                ?? $annotation->_context->interface
                ?? $annotation->_context->trait
                ?? $annotation->_context->enum
            );
        }

        return $className;
    }

    /**
     * 获取 annotation 上的 x 的某个属性
     */
    public static function getAnnotationXValue(AbstractAnnotation $annotation, string $key, mixed $default = null, bool $remove = false): mixed
    {
        if (!Generator::isDefault($annotation->x) && array_key_exists($key, $annotation->x)) {
            $value = $annotation->x[$key];
            if ($remove) {
                SwaggerHelper::removeAnnotationXValue($annotation, $key);
            }
            return $value;
        }
        return $default;
    }

    /**
     * 设置 annotation 上的 x 的某个属性
     */
    public static function setAnnotationXValue(AbstractAnnotation $annotation, string $key, mixed $value): void
    {
        if (Generator::isDefault($annotation->x)) {
            $annotation->x = [];
        }
        $annotation->x[$key] = $value;
    }

    /**
     * 移除 annotation 上的 x 的某个属性
     */
    public static function removeAnnotationXValue(AbstractAnnotation $annotation, string $key): void
    {
        if (!Generator::isDefault($annotation->x) && array_key_exists($key, $annotation->x)) {
            unset($annotation->x[$key]);
            if (!$annotation->x) {
                /** @phpstan-ignore-next-line */
                $annotation->x = Generator::UNDEFINED;
            }
        }
    }

    /**
     * 通过 property 构造一个 Schema
     * @template T of AnSchema
     * @param class-string<T> $schemaClass
     * @return T
     */
    public static function renewSchemaWithProperty(AnProperty $property, string $schemaClass = AnSchema::class): AnSchema
    {
        $schema = new $schemaClass(array_filter([
            'ref' => SwaggerHelper::getValue($property->ref),
            'description' => SwaggerHelper::getValue($property->description),
            'type' => SwaggerHelper::getValue($property->type),
            'format' => SwaggerHelper::getValue($property->format),
            'items' => SwaggerHelper::getValue($property->items),
            'default' => SwaggerHelper::getValue($property->default),
            'maximum' => SwaggerHelper::getValue($property->maximum),
            'minimum' => SwaggerHelper::getValue($property->minimum),
            'maxLength' => SwaggerHelper::getValue($property->maxLength),
            'minLength' => SwaggerHelper::getValue($property->minLength),
            'pattern' => SwaggerHelper::getValue($property->pattern),
            'enum' => SwaggerHelper::getValue($property->enum),
            'example' => SwaggerHelper::getValue($property->example),
            'nullable' => SwaggerHelper::getValue($property->nullable),
            'additionalProperties' => SwaggerHelper::getValue($property->additionalProperties),
        ], fn($item) => $item !== null));
        $schema->_context = $property->_context;
        return $schema;
    }

    /**
     * 通过 property 构造一个 Parameter
     */
    public static function renewParameterWithProperty(AnProperty $property, string $in, bool $required, bool $isForParameterRef = false): AnParameter
    {
        $schema = SwaggerHelper::renewSchemaWithProperty($property);
        $schema->_context = $property->_context;

        $parameter = new AnParameter(array_filter([
            'name' => $property->property,
            'description' => SwaggerHelper::getValue($property->description),
            'in' => $in,
            'required' => $required,
            'example' => SwaggerHelper::getValue($property->example),
            'examples' => SwaggerHelper::getValue($property->examples),
            'schema' => $schema,
        ]));
        if ($isForParameterRef) {
            // 作为 ref 使用时，该参数必须
            $parameter->parameter = $parameter->name;
        }
        $parameter->_context = $property->_context;

        return $parameter;
    }

    /**
     * 通过 property 构造一个 Header
     */
    public static function renewHeaderWithProperty(AnProperty $property, bool $required): AnHeader
    {
        $schema = SwaggerHelper::renewSchemaWithProperty($property);
        $schema->_context = $property->_context;

        $header = new AnHeader(array_filter([
            'header' => $property->property,
            'description' => SwaggerHelper::getValue($property->description),
            'required' => $required,
            'schema' => $schema,
        ]));
        $header->_context = $property->_context;

        return $header;
    }

    /**
     * 获取 operation 上的某个 mediaType
     */
    public static function getOperationRequestBodyMediaType(AnOperation $operation, string $mediaType): AnMediaType
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

    /**
     * 获取 response 上的某个 mediaType
     */
    public static function getResponseMediaType(AnResponse $response, string $mediaType): AnMediaType
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

    /**
     * 将 schema 添加到 mediaType
     */
    public static function appendSchema2mediaType(AnMediaType $mediaType, AnSchema $schema, Analysis $analysis): void
    {
        $isMediaTypeSchemaEmpty = false;
        if (Generator::isDefault($mediaType->schema)) {
            $mediaType->schema = new Schema();
            $isMediaTypeSchemaEmpty = true;
        }
        // 附加用的 schema，如果可以用 ref 的话，使用 ref
        $appendSchema = $schema;
        if (!Generator::isDefault($schema->schema)) {
            $refSchema = $analysis->getSchemaForSource(SwaggerHelper::getAnnotationClassName($schema));
            if ($refSchema) {
                $appendSchema = new Schema(ref: Components::ref($schema));
            } else {
                $appendSchema->schema = Generator::UNDEFINED; // 存在 schema 名的话，会影响 swagger-UI 的展示
            }
        }
        // mediaType 的 schema 是空的话，直接附加
        if ($isMediaTypeSchemaEmpty) {
            $mediaType->schema = $appendSchema;
            return;
        }
        // mediaType 的 schema 不为空，需要调整为 allOf
        $allOf = SwaggerHelper::getValue($mediaType->schema->allOf);
        if ($allOf === null) {
            // 原来不是 allOf 的情况，把 mediaType 的 schema 放入 allOf 中
            $allOf[] = clone $mediaType->schema;
        }
        // 补上需要添加的 schema
        $allOf[] = $appendSchema;
        // 设置到 mediaType 上
        $mediaType->schema->allOf = $allOf;
        /** @phpstan-ignore-next-line */
        $mediaType->schema->properties = Generator::UNDEFINED;
        /** @phpstan-ignore-next-line */
        $mediaType->schema->ref = Generator::UNDEFINED;
    }

    /**
     * $type 是否是上传文件的类型
     */
    public static function isTypeUploadedFile(mixed $type): bool
    {
        if (!is_string($type) || !str_contains($type, '\\')) {
            return false;
        }
        if (
            is_a($type, WebmanUploadedFile::class, true)
            || is_a($type, SymfonyUploadedFile::class, true)
        ) {
            return true;
        }
        return false;
    }
}
