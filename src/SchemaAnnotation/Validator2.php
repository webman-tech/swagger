<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

class Validator2
{
    private array $config = [
        'throw' => false, // 校验失败抛出异常
        'throw_type' => ValidateErrorException::class, // 抛出的异常类型
        'stopOnFirstError' => true, // 校验第一个失败后立即停止
        'validateSchemaType' => false, // 是否校验 OA\Property 上定义的类型，使用强类型时默认无需校验
    ];
    private array $defineTypeErrors = [];
    /**
     * @var array<string, array<int, array{type: string, message: string}>>
     */
    private array $errors = [];

    public function __construct(private BaseSchema $schema, private Getter $annotationGetter)
    {
    }

    /**
     * 类型赋值错误时增加错误用
     * @param string $property
     * @param \TypeError $error
     * @return void
     */
    public function addTypeError(string $property, \TypeError $error): void
    {
        // Cannot assign string to property A\B\C::$abc_xyz of type int
        preg_match('/Cannot assign (.*?) to property (.*?) of type (.*?)$/', $error->getMessage(), $matches);
        if (count($matches) === 4) {
            $this->defineTypeErrors[$property] = [
                'type' => 'type',
                'message' => "{$property} type must be {$matches[3]}",
            ];
        } else {
            $this->defineTypeErrors[$property] = [
                'type' => 'type',
                'message' => "{$property} type is error",
            ];
        }
    }

    public function validate(array $config = []): void
    {
        $this->config = array_merge($this->config, $config);

        // 必填校验
        $required = $this->annotationGetter->getSchema()->required;
        if (!$this->annotationGetter->isDefault($required)) {
            foreach ($required as $propertyName) {
                if (empty($this->schema->$propertyName)) {
                    $this->addError($propertyName, 'required', "{$propertyName} is Required");
                }
            }
        }
        // 参数类型校验
        if ($this->config['validateSchemaType']) {
            foreach ($this->annotationGetter->getProperties() as $propertyName => $property) {
                if (isset($this->defineTypeErrors[$propertyName])) {
                    continue;
                }
                $propertyType = $property->type;
                if ($this->annotationGetter->isDefault($propertyType)) {
                    continue;
                }
                $propertyValue = $this->schema->$propertyName;
                if (!$this->annotationGetter->checkType($propertyType, $propertyValue)) {
                    $this->defineTypeErrors[$propertyName] = [
                        'type' => 'type',
                        'message' => "{$propertyName} type must be {$propertyType}",
                    ];
                }
            }
        }
        foreach ($this->defineTypeErrors as $propertyName => $error) {
            $this->addError($propertyName, $error['type'], $error['message']);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function addError(string $propertyName, string $type, string $message): void
    {
        $this->errors[$propertyName][] = [
            'type' => $type,
            'message' => $message,
        ];

        if ($this->config['stopOnFirstError']) {
            $this->handleThrow();
        }
    }

    private function handleThrow(): void
    {
        if (!$this->errors) {
            return;
        }

        if ($this->config['throw']) {
            /** @var \Throwable $error */
            $error = new $this->config['throw_type'];
            if ($error instanceof ValidateErrorException) {
                $error->withErrors($this->errors);
            }
            throw $error;
        }
    }
}