<?php

namespace WebmanTech\Swagger\Helper;

/**
 * @internal
 */
final class ConfigHelper
{
    private static array $testKV = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$testKV[$key])) {
            return self::$testKV[$key];
        }

        return config("plugin.webman-tech.swagger.{$key}", $default);
    }

    private static ?string $viewPath = null;

    public static function getViewPath(): string
    {
        if (self::$viewPath !== null) {
            return self::$viewPath;
        }

        // 相对 app 目录的路径
        $guessPaths = [
            '../vendor/webman-tech/swagger/src', // 单独安装 webman-tech/swagger 时
            '../vendor/webman-tech/components-monorepo/packages/swagger/src', // 安装 webman-tech/components-monorepo 时
            '../packages/swagger/src', // 测试时使用
        ];
        foreach ($guessPaths as $guessPath) {
            if (is_dir(app_path() . '/' . $guessPath)) {
                return self::$viewPath = $guessPath;
            }
        }

        throw new \RuntimeException('找不到 swagger 模板路径');
    }

    /**
     * 测试用
     */
    public static function setForTest(?string $key = null, mixed $value = null): void
    {
        if ($key === null) {
            // reset
            self::$testKV = [];
            return;
        }
        self::$testKV[$key] = $value;
    }
}
