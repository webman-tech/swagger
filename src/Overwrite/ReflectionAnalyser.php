<?php

namespace WebmanTech\Swagger\Overwrite;

use OpenApi\Analysis;

/**
 * @internal
 */
final class ReflectionAnalyser extends \OpenApi\Analysers\ReflectionAnalyser
{
    protected function analyzeFqdn(string $fqdn, Analysis $analysis, array $details): Analysis
    {
        try {
            return parent::analyzeFqdn($fqdn, $analysis, $details);
        } catch (\Throwable $e) {
            if (
                str_contains($e->getMessage(), 'Class')
                && str_contains($e->getMessage(), 'not found')
            ) {
                // 忽略未定义的类的情况（webman初始框架没有引入 illuminate/database，会导致扫描分析报错）
                return $analysis;
            }
            throw $e;
        }
    }
}
