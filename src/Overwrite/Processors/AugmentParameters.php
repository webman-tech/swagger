<?php

namespace WebmanTech\Swagger\Overwrite\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

/**
 * @internal Fix bug
 * @link https://github.com/zircote/swagger-php/pull/1775/files
 */
final class AugmentParameters extends \OpenApi\Processors\AugmentParameters
{
    protected function augmentOperationParameters(Analysis $analysis): void
    {
        /** @var OA\Operation[] $operations */
        $operations = $analysis->getAnnotationsOfType(OA\Operation::class);

        foreach ($operations as $operation) {
            if (!Generator::isDefault($operation->parameters)) {
                $tags = [];
                $this->extractContent($operation->_context->comment, $tags);
                if (array_key_exists('param', $tags)) {
                    foreach ($tags['param'] as $name => $details) {
                        foreach ($operation->parameters as $parameter) {
                            if ($parameter->name == $name) {
                                if (Generator::isDefault($parameter->description) && $details['description']) {
                                    $parameter->description = $details['description'];
                                }
                            }
                        }
                    }
                }

                foreach ($operation->parameters as $parameter) {
                    if (!Generator::isDefault($parameter->schema)) {
                        $this->mapNativeType($parameter->schema, $parameter->schema->type);
                    }
                }
            }
        }
    }
}
