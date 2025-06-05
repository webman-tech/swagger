<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use WebmanTech\Swagger\DTO\SchemaConstants;

/**
 * 将定义的 schema 转为 parameters
 * @link https://github.com/zircote/swagger-php/blob/master/Examples/processors/schema-query-parameter/SchemaQueryParameter.php
 */
class SchemaQueryParameter
{
    const REF = SchemaConstants::X_SCHEMA_TO_PARAMETERS;

    private Analysis $analysis;

    public function __invoke(Analysis $analysis): void
    {
        $this->analysis = $analysis;
        /** @var Operation[] $operations */
        $operations = $analysis->getAnnotationsOfType(Operation::class);

        foreach ($operations as $operation) {
            if (!Generator::isDefault($operation->x) && array_key_exists(self::REF, $operation->x)) {
                if (!is_string($operation->x[SchemaConstants::X_SCHEMA_TO_PARAMETERS])) {
                    throw new \InvalidArgumentException('Value of `x.' . self::REF . '` must be a string');
                }

                $schema = $analysis->getSchemaForSource($operation->x[self::REF]);
                if (!$schema instanceof AnSchema) {
                    throw new \InvalidArgumentException('Value of `x.' . self::REF . "` contains reference to unknown schema: `{$operation->x[self::REF]}`");
                }

                $this->expandQueryArgs($operation, $schema);
                $this->cleanUp($operation);
            }
        }
    }

    /**
     * Expand the given operation by injecting parameters for all properties of the given schema.
     */
    private function expandQueryArgs(Operation $operation, AnSchema $schema): void
    {
        if (!Generator::isDefault($schema->allOf)) {
            // support allOf
            foreach ($schema->allOf as $itemSchema) {
                $this->expandQueryArgs($operation, $itemSchema);
            }
        }

        if (!Generator::isDefault($schema->ref)) {
            // support ref
            $refSchema = $this->analysis->openapi->ref((string)$schema->ref);
            if (!$refSchema instanceof AnSchema) {
                throw new \InvalidArgumentException('ref must be a schema reference');
            }
            $this->expandQueryArgs($operation, $refSchema);
        }

        if (Generator::isDefault($schema->properties) || !$schema->properties) {
            return;
        }

        $operation->parameters = Generator::isDefault($operation->parameters) ? [] : $operation->parameters;

        foreach ($schema->properties as $property) {
            $isNullable = Generator::isDefault($property->nullable) ? false : $property->nullable;
            $schemaNew = new Schema(
                type: Generator::isDefault($property->format) ? $property->type : $property->format,
                nullable: $isNullable
            );
            $schemaNew->_context = $operation->_context; // inherit context from operation, required to pretend to be a parameter

            $parameter = new Parameter(
                name: $property->property,
                description: Generator::isDefault($property->description) ? null : $property->description,
                in: 'query',
                required: !$isNullable,
                schema: $schemaNew,
                example: $property->example,
            );
            $parameter->_context = $operation->_context; // inherit context from operation, required to pretend to be a parameter

            $operation->parameters[] = $parameter;
        }
    }

    private function cleanUp(Operation $operation): void
    {
        unset($operation->x[self::REF]);
        if (!$operation->x) {
            /* @phpstan-ignore-next-line */
            $operation->x = Generator::UNDEFINED;
        }
    }
}
