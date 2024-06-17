<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use WebmanTech\Swagger\DTO\ConfigSchemaValidatorDTO;

class SchemaValidator
{
    public function __construct(private ReflectionClassReader $reader)
    {
    }

    public function scanRules(string $class): array
    {
        $rules = [];
        foreach ($this->reader->read($class) as $property => $data) {
            $rule = [];
            if ($data['required']) {
                $rule[] = 'required';
            }
            if ($data['type']) {
                $rule[] = $data['type'];
            }
            $rules[$property] = $rule;
        }
        return $rules;
    }

    public function validate(array $data, string $class)
    {
        $rules = $this->scanRules($class);
        $this->validator->validate($data, $rules);
    }
}
