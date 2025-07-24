<?php

namespace WebmanTech\Swagger\Enums;

use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\Property as AnProperty;
use OpenApi\Annotations\Schema as AnSchema;
use WebmanTech\DTO\Enums\RequestPropertyInEnum;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;

enum PropertyInEnum: string
{
    case Query = 'query';
    case Path = 'path';
    case Header = 'header';
    case Cookie = 'cookie';
    case Body = 'body';
    case Form = 'form';
    case Json = 'json';
    case Post = 'post'; // alias json
    case Get = 'get'; // alias query

    public const REQUEST_PARAMETERS = [
        self::Query,
        self::Path,
        self::Header,
        self::Cookie,
    ];

    private static function tryFromAnnotationX(AbstractAnnotation $annotation, ?self $default = null, ?array $enabledList = null): ?self
    {
        $propertyIn = SwaggerHelper::getAnnotationXValue($annotation, SchemaConstants::X_PROPERTY_IN, $default);
        if ($propertyIn === null) {
            return null;
        }
        $case = is_string($propertyIn) ? self::from($propertyIn) : $propertyIn;
        $case = match ($case) {
            self::Post => self::Json,
            self::Get => self::Query,
            default => $case,
        };
        /** @var PropertyInEnum $case */
        if ($enabledList !== null) {
            if (!in_array($case, $enabledList, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'only support %s, given %s, in %s',
                        implode(',', array_map(fn(self $item) => $item->value, $enabledList)),
                        $case->value,
                        $annotation->_context,
                    )
                );
            }
        }
        return $case;
    }

    public static function tryFromSchemaX(AnSchema $schema, ?self $default = null): ?self
    {
        return self::tryFromAnnotationX($schema, $default, [
            self::Json,
            self::Form,
            ...self::REQUEST_PARAMETERS,
        ]);
    }

    public static function tryFromPropertyX(AnProperty $property, ?self $default = null): ?self
    {
        return self::tryFromAnnotationX($property, $default, [
            self::Json,
            self::Form,
            self::Body,
            ...self::REQUEST_PARAMETERS,
        ]);
    }

    public static function tryFromDTORequestPropertyIn(RequestPropertyInEnum $requestPropertyInEnum): self
    {
        return match ($requestPropertyInEnum) {
            RequestPropertyInEnum::Query => self::Query,
            RequestPropertyInEnum::Path => self::Path,
            RequestPropertyInEnum::Header => self::Header,
            RequestPropertyInEnum::Cookie => self::Cookie,
            RequestPropertyInEnum::Body => self::Body,
            RequestPropertyInEnum::Form => self::Form,
            RequestPropertyInEnum::Json => self::Json,
        };
    }

    public function toParameterIn(): string
    {
        return $this->value;
    }
}
