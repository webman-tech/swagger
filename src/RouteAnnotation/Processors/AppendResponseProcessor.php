<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Attributes\Response;
use OpenApi\Generator;

/**
 * 给 Operation 添加必须的 response
 */
final class AppendResponseProcessor
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);

        foreach ($operations as $operation) {
            if (Generator::isDefault($operation->responses)) {
                $operation->responses = [];
            }
            if (count($operation->responses) === 0) {
                $operation->responses[200] = new Response(
                    response: 200,
                    description: 'OK',
                );
            }
        }
    }
}
