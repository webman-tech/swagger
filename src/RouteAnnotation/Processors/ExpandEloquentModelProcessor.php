<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Analysis;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Property;
use OpenApi\Processors\Concerns\TypesTrait;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * 自动扫描 Eloquent Model 的属性，生成 Schema 的 属性
 */
final class ExpandEloquentModelProcessor
{
    use TypesTrait;

    public function __construct(
        private bool $enabled = true,
    )
    {
        if (!class_exists(Model::class)) {
            $this->enabled = false;
        }
    }

    public function __invoke(Analysis $analysis): void
    {
        if (!$this->enabled) {
            return;
        }

        /** @var AnSchema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(AnSchema::class);

        foreach ($schemas as $schema) {
            if (!$schema->_context->is('class')) {
                continue;
            }
            $className = $schema->_context->fullyQualifiedName($schema->_context->class);
            if (!is_a($className, Model::class, true)) {
                continue;
            }
            $schema->properties = array_merge(
                SwaggerHelper::getValue($schema->properties, []),
                $this->getModelProperties($className),
            );
        }
    }

    /**
     * @param class-string<Model> $className
     */
    private function getModelProperties(string $className): array
    {
        $properties = [];
        $reflectionClass = new \ReflectionClass($className);
        $docComment = $reflectionClass->getDocComment();

        $model = new $className;
        $visibleAttributes = $model->getVisible();
        $hiddenAttributes = $model->getHidden();

        if ($docComment) {
            $matches = [];
            preg_match_all('/@property\s+(.*?)\s+(.*?)\s(.*)/', $docComment, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                $type = $matches[1][$i];
                $name = $matches[2][$i];
                $desc = $matches[3][$i];
                // name 处理
                if (!str_starts_with($name, '$')) {
                    continue;
                }
                $name = substr($name, 1);
                // visible
                if ($visibleAttributes && !in_array($name, $visibleAttributes, true)) {
                    continue;
                }
                // hidden
                if (in_array($name, $hiddenAttributes, true)) {
                    continue;
                }
                // nullable
                $nullable = str_contains($type, '|null');
                if ($nullable) {
                    $type = str_replace('|null', '', $type);
                }
                // type 特殊处理
                if (str_contains($type, '|')) {
                    // 还有多种类型的话，不支持
                    $type = 'mixed';
                }
                if (is_a($type, \DateTimeInterface::class, true)) {
                    $type = 'datetime';
                }
                // property
                $property = new Property(
                    property: $name,
                    description: $desc,
                );
                $this->mapNativeType($property, $type);
                if ($nullable) {
                    $property->nullable = true;
                }

                $properties[] = $property;
            }
        }

        return $properties;
    }
}
