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
                unset(
                    $operation->x[SchemaConstants::X_NAME],
                    $operation->x[SchemaConstants::X_PATH],
                    $operation->x[SchemaConstants::X_MIDDLEWARE],
                );
                if (!$operation->x) {
                    $operation->x = Generator::UNDEFINED;
                }
            }
        }
    }
}