<?php

namespace WebmanTech\Swagger\Helper;

/**
 * @internal
 */
final class ConfigHelper
{
    public static function get(string $key, mixed $default = null): mixed
    {
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
            '../vendor/webman-tech/swagger/src',
            '../vendor/webman-tech/components-monorepo/packages/swagger/src',
        ];
        foreach ($guessPaths as $guessPath) {
            if (is_dir(app_path() . '/' . $guessPath)) {
                return self::$viewPath = $guessPath;
            }
        }

        throw new \RuntimeException('找不到 swagger 模板路径');
    }
}
