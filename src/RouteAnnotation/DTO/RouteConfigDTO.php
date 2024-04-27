<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

/**
 * @property string $desc
 * @property string $method
 * @property string $path
 * @property string $controller
 * @property string $action
 * @property array|<string, <string, RequestParamDTO>> $request_param
 * @property array|<string, <string, RequestBodyDTO>> $request_body
 * @property bool $request_body_required
 */
class RouteConfigDTO extends BaseDTO
{
    public function __construct(array $data = [])
    {
        $data = array_merge([
            'desc' => '',
            'method' => '',
            'path' => '',
            'controller' => '',
            'action' => '',
            'request_param' => [],
            'request_body' => [],
            'request_body_required' => false,
        ], $data);

        parent::__construct($data);

        foreach ($this->request_param as $in => $value) {
            foreach ($value as $name => $config) {
                if (is_array($config)) {
                    $tempValue = $this->request_param;
                    $tempValue[$in][$name] = new RequestParamDTO($config);
                    $this->request_param = $tempValue;
                }
            }
        }

        foreach ($this->request_body as $mediaType => $value) {
            foreach ($value as $name => $config) {
                if (is_array($config)) {
                    $tempValue = $this->request_body;
                    $tempValue[$mediaType][$name] = new RequestBodyDTO($config);
                    $this->request_body = $tempValue;
                }
            }
            unset($v);
        }
        unset($value);
    }
}