<?php

use Illuminate\Validation\ValidationException;
use Tests\Fixtures\SchemaExampleSimple;

test('load no validate, toArray, json', function () {
    $data = [
        'string' => 'string',
        'int' => 1,
        'bool' => true,
        'float' => 1.2,
        'union' => 1,
        'object' => [
            'string' => 'string',
            'int' => 1,
        ],
        'array' => [
            [
                'string' => 'string',
                'int' => 1,
            ],
        ],
        'notExist' => 'string',
        'mixed' => 'string',
        'nullable' => 'string',
    ];
    $result = $data;
    unset($result['notExist']); // 不会包含未通过 OA\Property 的值

    $schema = SchemaExampleSimple::create($data);
    expect($schema->toArray())->toBe($result)
        ->and(json_encode($schema))->toBe(json_encode($result));
});

test('load no validate, no params', function () {
    $result = [
        'string' => '',
        'int' => 0,
        'bool' => false,
        'float' => 0.0,
        'union' => 0,
        'object' => null,
        'array' => [],
        'mixed' => 1,
        'nullable' => null,
    ];

    $schema = SchemaExampleSimple::create([]);
    expect($schema->toArray())->toBe($result);
});

test('load with validate', function () {
    expect(function() {
        return SchemaExampleSimple::create([], get_validator());
    })->toThrow(ValidationException::class);
});

test('validate union(不支持)', function () {
    $data = [
        'string' => 'string',
        'bool' => true,
        'union' => [1,2],
    ];
    $schema = SchemaExampleSimple::create($data, get_validator());
    expect($schema->union)->toBe(0);
});
