<?php

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\SchemaAnnotation\ReflectionClassReader;

#[OA\Schema(required: ['name'])]
class Example1
{
    #[OA\Property(description: '姓名', example: '张三')]
    public string $name;
    #[OA\Property]
    public int $age;
    #[OA\Property(description: '性别', type: 'integer')]
    public $sex;
}

test('', function () {
    $reader = new ReflectionClassReader();
    $data = $reader->read(Example1::class);
    expect($data)->toBe([
        'name' => ['required' => true, 'type' => 'string'],
        'age' => ['required' => false, 'type' => 'integer'],
        'sex' => ['required' => false, 'type' => 'integer'],
    ]);
});
