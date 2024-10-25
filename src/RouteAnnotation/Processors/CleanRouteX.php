<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Generator;
use OpenApi\Processors\ProcessorInterface;
use OpenApi\Annotations as OA;
use WebmanTech\Swagger\DTO\SchemaConstants;

class CleanRouteX implements ProcessorInterface
{
    public function __invoke(Analysis $analysis)
    {
        /** @var OA\Operation[] $operations */
        $operations = $analysis->merged()->getAnnotationsOfType(OA\Operation::class);

        foreach ($operations as $operation) {
            if (!Generator::isDefault($operation->x)) {
                $x = $operation->x;
                unset(
                    $x[SchemaConstants::X_NAME],
                    $x[SchemaConstants::X_PATH],
                    $x[SchemaConstants::X_MIDDLEWARE],
                );
                $operation->x = $x;
            }
        }
    }
}