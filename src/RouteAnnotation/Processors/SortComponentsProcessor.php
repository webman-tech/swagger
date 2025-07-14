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
    public function __invoke(Analysis $analysis)
    {
        if (is_object($analysis->openapi->components) && is_iterable($analysis->openapi->components->schemas)) {
            usort($analysis->openapi->components->schemas, function (Schema $a, Schema $b) {
                return strcmp($a->schema, $b->schema);
            });
        }
        if (is_iterable($analysis->openapi->paths)) {
            usort($analysis->openapi->paths, function (PathItem $a, PathItem $b) {
                return strcmp($a->path, $b->path);
            });
        }
    }
}
