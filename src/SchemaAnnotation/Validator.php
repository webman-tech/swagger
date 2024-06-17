<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

class Validator
{
    public function __construct(private ReflectionClassReader $reader, private ValidatorFactory $validator)
    {
    }

    public function getRules(string $class, array $extra = []): array
    {
        $rules = [];
        foreach ($this->reader->read($class) as $property => $data) {
            $rule = [];
            if ($data['required']) {
                $rule[] = 'required';
            }
            if ($data['types']) {
                foreach ($data['types'] as $type) {
                    $rule[] = $this->getValidateType($type);
                }
            }
            if (isset($extra[$property])) {
                $rule = array_merge($rule, $extra[$property]);
            }
            $rules[$property] = $rule;
        }
        return $rules;
    }

    private function getValidateType(string $type): string
    {
        return match ($type) {
            'float' => 'numeric',
            'object' => 'array',
            default => $type,
        };
    }

    public function validate(array $data, string $class)
    {
        $rules = $this->scanRules($class);
        $this->validator->validate($data, $rules);
    }
}
