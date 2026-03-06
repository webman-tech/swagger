<?php

namespace WebmanTech\Swagger\RouteAnnotation\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\MediaType as AnMediaType;
use OpenApi\Annotations\Operation as AnOperation;
use OpenApi\Annotations\Schema as AnSchema;
use OpenApi\Generator;
use WebmanTech\DTO\BaseRequestDTO;
use WebmanTech\Swagger\DTO\SchemaConstants;
use WebmanTech\Swagger\Helper\SwaggerHelper;

final class AppendValidationRulesToOperationDescriptionProcessor
{
    public function __construct(private readonly bool $enabled = true)
    {
    }

    public function __invoke(Analysis $analysis): void
    {
        if (!$this->enabled) {
            return;
        }

        /** @var AnOperation[] $operations */
        $operations = $analysis->getAnnotationsOfType(AnOperation::class);
        foreach ($operations as $operation) {
            $validationRules = $this->getOperationValidationRules($operation, $analysis);
            if (!$validationRules) {
                continue;
            }
            $this->appendValidationRulesInDescription($operation, $validationRules);
        }
    }

    private function getOperationValidationRules(AnOperation $operation, Analysis $analysis): array
    {
        if (Generator::isDefault($operation->requestBody) || Generator::isDefault($operation->requestBody->content)) {
            return [];
        }

        foreach ($operation->requestBody->content as $mediaType) {
            if (!$mediaType instanceof AnMediaType || Generator::isDefault($mediaType->schema)) {
                continue;
            }

            foreach ($this->resolveRequestSchemas($mediaType->schema, $analysis) as $schema) {
                $className = SwaggerHelper::getAnnotationClassName($schema);
                if (!$className || !is_a($className, BaseRequestDTO::class, true)) {
                    continue;
                }
                $validationRules = SwaggerHelper::getAnnotationXValue($schema, SchemaConstants::X_SCHEMA_VALIDATION_RULES);
                if (is_array($validationRules) && $validationRules) {
                    return $validationRules;
                }
            }
        }

        return [];
    }

    /**
     * @return AnSchema[]
     */
    private function resolveRequestSchemas(AnSchema $schema, Analysis $analysis): array
    {
        $schemas = [];

        if (!Generator::isDefault($schema->ref)) {
            $resolved = $this->findSchemaByRef($schema->ref, $analysis);
            if ($resolved) {
                $schemas[] = $resolved;
            }
        }

        foreach (['allOf', 'oneOf', 'anyOf'] as $combineType) {
            $children = SwaggerHelper::getValue($schema->{$combineType}, []);
            foreach ($children as $childSchema) {
                if ($childSchema instanceof AnSchema) {
                    $schemas = array_merge($schemas, $this->resolveRequestSchemas($childSchema, $analysis));
                }
            }
        }

        if (!$schemas && Generator::isDefault($schema->ref)) {
            $schemas[] = $schema;
        }

        return $schemas;
    }

    private function findSchemaByRef(string $ref, Analysis $analysis): ?AnSchema
    {
        $prefix = '#/components/schemas/';
        if (!str_starts_with($ref, $prefix)) {
            return null;
        }
        $schemaName = substr($ref, strlen($prefix));
        $componentSchemas = SwaggerHelper::getValue($analysis->openapi->components->schemas, []);
        foreach ($componentSchemas as $schema) {
            if ($schema instanceof AnSchema && SwaggerHelper::getValue($schema->schema) === $schemaName) {
                return $schema;
            }
        }

        return null;
    }

    private function appendValidationRulesInDescription(AnOperation $operation, array $validationRules): void
    {
        if (Generator::isDefault($operation->description)) {
            $operation->description = '';
        } else {
            $operation->description .= "\n";
        }

        $content = [
            '```php',
            '// Validation Rules',
            '[',
        ];
        foreach ($validationRules as $key => $rules) {
            $ruleStr = implode(', ', array_map(fn($rule) => is_string($rule) ? "'{$rule}'" : ('__' . gettype($rule)), $rules));
            $content[] = "    '{$key}' => [{$ruleStr}],";
        }
        $content[] = ']';
        $content[] = '```';
        $operation->description .= implode("\n", $content);
    }
}
