<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use Illuminate\Support\Str;
use OpenApi\Analysis;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * 枚举展示时显示描述信息
 * @link https://github.com/zircote/swagger-php/issues/1661
 * @link https://github.com/DerManoMann/openapi-extras/blob/main/src/Processors/EnumDescription.php
 */
final readonly class EnumDescriptionProcessor
{
    public function __construct(
        private bool    $enabled = true,
        private ?string $descriptionMethod = 'description',
    )
    {
    }

    public function __invoke(Analysis $analysis)
    {
        if (!$this->enabled) {
            return;
        }

        /** @var AnSchema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(AnSchema::class);
        // 仅处理 Schema 即可，子类（比如 Property 等）不需要
        $schemas = array_filter(
            $schemas,
            fn(AnSchema $schema) => in_array($schema::class, [AnSchema::class, Schema::class]),
        );

        foreach ($schemas as $schema) {
            if (!Generator::isDefault($schema->enum) && $schema->_context->is('enum')) {
                $className = $schema->_context->fullyQualifiedName($schema->_context->enum);
                $caseDesc = [];
                if (is_a($className, \BackedEnum::class, true)) {
                    foreach ($schema->enum as $item) {
                        $case = $className::tryFrom($item);
                        $itemDescription = method_exists($case, $this->descriptionMethod) ? $case->{$this->descriptionMethod}() : $case->name;
                        if ($this->isValueEqDescription($item, $itemDescription)) {
                            continue;
                        }
                        $caseDesc[] = "- {$item}: {$itemDescription}";
                    }
                }
                if (!$caseDesc) {
                    continue;
                }
                $caseDesc = implode("\n", $caseDesc);

                $description = SwaggerHelper::getValue($schema->description, '');
                if ($description) {
                    $schema->description = "{$description}\n{$caseDesc}";
                } else {
                    $schema->description = $caseDesc;
                }
            }
        }
    }

    private function isValueEqDescription(string $value, string $description): bool
    {
        return $value === $description
            || Str::of($value)->replace(['_', '-'], '')->lower()->toString() === Str::of($description)->replace(['_', '-'], '')->lower()->toString();
    }
}
