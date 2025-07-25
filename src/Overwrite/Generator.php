<?php

namespace WebmanTech\Swagger\Overwrite;

use Closure;
use Illuminate\Support\Str;
use OpenApi\Analysis as OAAnalysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Pipeline;
use OpenApi\Processors as OAProcessors;
use WebmanTech\Swagger\DTO\ConfigOpenapiDocDTO;
use WebmanTech\Swagger\Helper\SwaggerHelper;
use WebmanTech\Swagger\RouteAnnotation;

/**
 * @internal
 */
final class Generator extends \OpenApi\Generator
{
    private bool $schemaNameShouldFormat = false;
    private null|Closure $schemaNameUseClassNameFormat = null;

    public function __construct(private readonly ConfigOpenapiDocDTO $openapiDocConfig)
    {
        parent::__construct();

        if ($this->openapiDocConfig->schema_name_format_use_classname !== null) {
            $this->schemaNameShouldFormat = true;
            $formatter = $this->openapiDocConfig->schema_name_format_use_classname;
            $this->schemaNameUseClassNameFormat = $formatter instanceof Closure
                ? $formatter
                : fn(string $className) => Str::of($className)
                    ->ltrim('\\')
                    ->replace('\\', ' ')
                    ->studly()
                    ->__toString();
        }
    }

    public function formatSchemaName(OA\Schema $schema): void
    {
        if (!$this->schemaNameShouldFormat) {
            return;
        }
        if (!self::isDefault($schema->schema)) {
            // 主动命名过的不处理
            return;
        }
        if ($this->schemaNameUseClassNameFormat && ($className = SwaggerHelper::getAnnotationClassName($schema))) {
            $schema->schema = $this->schemaNameUseClassNameFormat->call($this, $className);
        }
    }

    public function init(): self
    {
        /** @phpstan-ignore-next-line */
        return $this
            ->setAliases(self::DEFAULT_ALIASES)
            ->setNamespaces(self::DEFAULT_NAMESPACES)
            ->setAnalyser(new ReflectionAnalyser(
                annotationFactories: [
                    new Analysers\AttributeAnnotationFactory(
                        autoLoadSchemaClasses: $this->openapiDocConfig->auto_load_schema_classes,
                    ),
                ],
            ))
            ->withProcessorPipeline(function (Pipeline $pipeline): void {
                if ($this->schemaNameUseClassNameFormat) {
                    $pipeline
                        ->remove(null, function (&$pipe) {
                            // 替换实现
                            if ($pipe instanceof OAProcessors\AugmentSchemas) {
                                $pipe = new Processors\AugmentSchemas($this->formatSchemaName(...));
                            }
                            if ($pipe instanceof OAProcessors\ExpandEnums) {
                                $pipe = new Processors\ExpandEnums($this->formatSchemaName(...));
                            }
                            return true;
                        });
                }

                $pipeline
                    ->remove(OAProcessors\CleanUnusedComponents::class)
                    ->remove(null, function (&$pipe) {
                        // 替换实现
                        if ($pipe instanceof OAProcessors\AugmentParameters) {
                            $pipe = new Processors\AugmentParameters();
                        }
                        return true;
                    })
                    ->add(new RouteAnnotation\Processors\ExpandEloquentModelProcessor(
                        enabled: $this->openapiDocConfig->expand_eloquent_model_enable,
                    ))
                    ->add(new RouteAnnotation\Processors\ExpandDTOAttributionsProcessor())
                    ->add(new RouteAnnotation\Processors\MergeClassInfoProcessor())
                    ->add(new RouteAnnotation\Processors\ExpandEnumDescriptionProcessor(
                            enabled: $this->openapiDocConfig->schema_enum_description_enable,
                            descriptionMethod: $this->openapiDocConfig->schema_enum_description_method)
                    )
                    ->add(new RouteAnnotation\Processors\XSchemaPropertyInProcessor())
                    ->add(new RouteAnnotation\Processors\XSchemaRequestProcessor())
                    ->add(new RouteAnnotation\Processors\XSchemaResponseProcessor())
                    ->add(new RouteAnnotation\Processors\AppendResponseProcessor())
                    ->add(new RouteAnnotation\Processors\ResponseLayoutProcessor(
                        layoutClass: $this->openapiDocConfig->response_layout_class,
                        layoutDataCode: $this->openapiDocConfig->response_layout_data_code,
                    ))
                    ->add(new OAProcessors\CleanUnusedComponents(
                        enabled: $this->openapiDocConfig->clean_unused_components_enable,
                    ))
                    ->add(new RouteAnnotation\Processors\SortComponentsProcessor())
                    ->add(new RouteAnnotation\Processors\XCleanProcessor());
            });
    }

    public function generate(iterable $sources, ?OAAnalysis $analysis = null, bool $validate = true): ?OA\OpenApi
    {
        $analysis ??= new Analysis([], new Context([
            'version' => $this->getVersion(),
            'logger' => $this->getLogger(),
        ]));

        if ($this->schemaNameShouldFormat && $analysis instanceof Analysis) {
            $analysis->setSchemaNameFormatter($this->formatSchemaName(...));
        }

        return parent::generate($sources, $analysis, $validate);
    }
}
