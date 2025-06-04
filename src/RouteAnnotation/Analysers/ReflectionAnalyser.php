<?php

namespace WebmanTech\Swagger\RouteAnnotation\Analysers;

use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysis;

class ReflectionAnalyser extends \OpenApi\Analysers\ReflectionAnalyser
{
    public function __construct()
    {
        parent::__construct([
            new DocBlockAnnotationFactory(),
            new AttributeAnnotationFactory()
        ]);
    }

    protected function analyzeFqdn(string $fqdn, Analysis $analysis, array $details): Analysis
    {
        try {
            return parent::analyzeFqdn($fqdn, $analysis, $details);
        } catch (\Throwable $e) {
            if (
                strpos($e->getMessage(), 'Class') !== false
                && strpos($e->getMessage(), 'not found') !== false
            ) {
                // 忽略未定义的类的情况（webman初始框架没有引入 illuminate/database，会导致扫描分析报错）
                return $analysis;
            }
            throw $e;
        }
    }
}