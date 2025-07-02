<?php

namespace WebmanTech\Swagger\Helper;

final class ArrayHelper
{
    /**
     * 合并数组
     * @link https://github.com/yiisoft/arrays/blob/master/src/ArrayHelper.php::merge
     * @param ...$arrays
     * @return array
     */
    public static function merge(...$arrays): array
    {
        $result = array_shift($arrays) ?: [];
        while (!empty($arrays)) {
            /** @var mixed $value */
            foreach (array_shift($arrays) as $key => $value) {
                if (is_int($key)) {
                    if (array_key_exists($key, $result)) {
                        if ($result[$key] !== $value) {
                            /** @var mixed */
                            $result[] = $value;
                        }
                    } else {
                        /** @var mixed */
                        $result[$key] = $value;
                    }
                } elseif (isset($result[$key]) && is_array($value) && is_array($result[$key])) {
                    $result[$key] = self::merge($result[$key], $value);
                } else {
                    /** @var mixed */
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * 转化数组为 swagger-ui 需要的 js 格式参数
     * @param array $params
     * @return string
     */
    public static function formatUIParams(array $params): string
    {
        $expressions = [];
        $params = self::processData($params, $expressions, uniqid('', true));

        $json = json_encode($params);

        return strtr($json, $expressions);
    }

    /**
     * 处理数据
     * @param $data
     * @param $expressions
     * @param string $expPrefix
     * @return mixed|string
     */
    private static function processData($data, &$expressions, string $expPrefix)
    {
        if ($data instanceof JsExpression) {
            $token = "!{[$expPrefix=" . count($expressions) . ']}!';
            $expressions['"' . $token . '"'] = (string)$data;

            return $token;
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = self::processData($value, $expressions, $expPrefix);
                }
            }
        }
        return $data;
    }
}
