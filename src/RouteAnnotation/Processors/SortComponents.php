<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;

/**
 * 对组件排序，防止扫描顺序不一致，导致多次扫描输出结果顺序不一致的情况
 * @link https://github.com/zircote/swagger-php/blob/master/docs/examples/processors/sort-components/SortComponents.php
 */
class SortComponents
{
    public function __invoke(Analysis $analysis)
    {
        if (is_object($analysis->openapi->components) && is_iterable($analysis->openapi->components->schemas)) {
            usort($analysis->openapi->components->schemas, function ($a, $b) {
                return strcmp($a->schema, $b->schema);
            });
        }
    }
}
