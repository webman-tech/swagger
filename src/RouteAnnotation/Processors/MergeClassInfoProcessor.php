<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Tag as AnTag;
use OpenApi\Generator;
use WebmanTech\Swagger\Helper\SwaggerHelper;

/**
 * 将 class 级别的一些信息聚合到 operation 上
 */
final class MergeClassInfoProcessor
{
    public function __construct(
        private readonly string $skipClassTag = '--class-skip', // 用于忽略 class Tag 的标记
        private readonly bool   $classTagFirst = true, // 将 class 上的 tag 放到 operation 的最前面
    )
    {
    }

    public function __invoke(Analysis $analysis): void
    {
        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);

        foreach ($operations as $operation) {
            $parentClass = SwaggerHelper::getAnnotationClassName($operation);
            if (!$parentClass) {
                continue;
            }
            // tag
            $tag = $analysis->getAnnotationForSource($parentClass, AnTag::class);
            if ($tag) {
                $this->appendTagToOperation($operation, $tag);
            }
        }
    }

    private function appendTagToOperation(AnOperation $operation, AnTag $tagParent): void
    {
        if (Generator::isDefault($operation->tags)) {
            $operation->tags = [];
        }
        if (in_array($this->skipClassTag, $operation->tags, true)) {
            // 忽略 class 上的 tag
            $operation->tags = array_values(array_filter($operation->tags, function ($tag) {
                return $tag !== $this->skipClassTag;
            }));
            return;
        }
        if (!in_array($tagParent->name, $operation->tags, true)) {
            // 将 class 上的 tag 添加到 operation
            if ($this->classTagFirst) {
                array_unshift($operation->tags, $tagParent->name);
            } else {
                $operation->tags[] = $tagParent->name;
            }
        }
    }
}
