<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;

class CleanRouteX
{
    public function __invoke(Analysis $analysis): void
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
                    /* @phpstan-ignore-next-line */
                    $operation->x = Generator::UNDEFINED;
                }
            }
        }
    }
}
