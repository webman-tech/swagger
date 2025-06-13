<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Tag as AnTag;
use OpenApi\Generator;

/**
 * 将 class 级别的一些信息聚合到 operation 上
 */
class MergeClassLevelInfo
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);

        foreach ($operations as $operation) {
            $parentClass = ($operation->_context->namespace ?? '') . '\\' . ($operation->_context->class ?? '');
            if ($parentClass === '\\') {
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
        if (!in_array($tagParent->name, $operation->tags, true)) {
            $operation->tags[] = $tagParent->name;
        }
    }
}
