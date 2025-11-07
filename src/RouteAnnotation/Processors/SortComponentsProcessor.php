<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\PathItem;
use OpenApi\Annotations\Schema;

/**
 * 对组件排序，防止扫描顺序不一致，导致多次扫描输出结果顺序不一致的情况
 * @link https://github.com/zircote/swagger-php/blob/master/docs/examples/processors/sort-components/SortComponents.php
 */
final class SortComponentsProcessor
{
    public function __construct(
        private readonly bool $enabled = true,
        private readonly bool $sortSchemas = true, // components 排序方便查找
        private readonly bool $sortPaths = false, // paths 默认不排序，与代码编写顺序一致
    )
    {
    }

    public function __invoke(Analysis $analysis): void
    {
        if (!$this->enabled || !$analysis->openapi) {
            return;
        }

        if ($this->sortSchemas) {
            /** @phpstan-ignore-next-line */
            if (is_object($analysis->openapi->components) && is_iterable($analysis->openapi->components->schemas)) {
                usort($analysis->openapi->components->schemas, fn(Schema $a, Schema $b) => strcmp($a->schema, $b->schema));
            }
        }
        if ($this->sortPaths) {
            /** @phpstan-ignore-next-line */
            if (is_iterable($analysis->openapi->paths)) {
                usort($analysis->openapi->paths, fn(PathItem $a, PathItem $b) => strcmp($a->path, $b->path));
            }
        }
    }
}
