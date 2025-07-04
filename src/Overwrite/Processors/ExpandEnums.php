<?php

namespace WebmanTech\Swagger\Overwrite\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

/**
 * @internal
 */
final class ExpandEnums extends \OpenApi\Processors\ExpandEnums
{
    public function __construct(private readonly \Closure $schemaNameFormatter)
    {
        parent::__construct();
    }

    protected function expandContextEnum(Analysis $analysis): void
    {
        /** @var OA\Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(OA\Schema::class, true);

        foreach ($schemas as $schema) {
            if ($schema->_context->is('enum')) {
                if (Generator::isDefault($schema->schema)) {
                    call_user_func($this->schemaNameFormatter, $schema);
                }
            }
        }

        parent::expandContextEnum($analysis);
    }
}
