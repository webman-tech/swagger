<?php

namespace Unit;

use Tests\Fixtures\SchemaNested;
use Tests\Fixtures\SchemaNestedHasRequired;
use Tests\Fixtures\SchemaNestedWithNested;
use WebmanTech\Swagger\SchemaAnnotation\DTO\ClassInfoDTO;

beforeEach(function () {
    $this->classInfo = new ClassInfoDTO([
        'required' => ['string', 'bool'],
        'nullable' => ['mixed', 'nullable', 'nullable2'],
        'propertyTypes' => [
            'string' => 'string',
            'string2' => 'string',
            'int' => 'integer',
            'int2' => 'integer',
            'bool' => 'boolean',
            'bool2' => 'boolean',
            'float' => 'float',
            'number' => 'number',
            'union' => ['string', 'integer'],
            'union2' => ['string', 'integer'],
            'object' => 'object_' . SchemaNestedHasRequired::class,
            'object2' => 'object_' . SchemaNestedHasRequired::class,
            'object3' => 'object_' . SchemaNestedWithNested::class,
            'array' => 'array_' . SchemaNestedHasRequired::class,
            'array2' => 'array_' . SchemaNestedHasRequired::class,
            'array3' => 'array',
            'mixed' => 'mixed',
            'mixed2' => 'mixed',
            'nullable' => 'string',
            'nullable2' => 'mixed',
        ],
    ]);

    $this->classInfoNested = new ClassInfoDTO([
        'propertyTypes' => [
            'object' => 'object_' . SchemaNested::class,
        ],
    ]);

    $this->classInfoSimple = new ClassInfoDTO([
        'required' => ['string', 'bool'],
        'propertyTypes' => [
            'string' => 'string',
            'string2' => 'string',
            'bool' => 'boolean',
            'bool2' => 'boolean',
        ],
    ]);
});

test('required', function () {
    expect($this->classInfo->required)->toBe(['string', 'bool']);
});

test('nullable', function () {
    expect($this->classInfo->nullable)->toBe(['mixed', 'nullable', 'nullable2']);
});

test('propertyTypes', function () {
    expect($this->classInfo->propertyTypes)->toBe([
        'string' => 'string',
        'string2' => 'string',
        'int' => 'integer',
        'int2' => 'integer',
        'bool' => 'boolean',
        'bool2' => 'boolean',
        'float' => 'float',
        'number' => 'number',
        'union' => ['string', 'integer'],
        'union2' => ['string', 'integer'],
        'object' => 'object_' . SchemaNestedHasRequired::class,
        'object2' => 'object_' . SchemaNestedHasRequired::class,
        'object3' => 'object_' . SchemaNestedWithNested::class,
        'array' => 'array_' . SchemaNestedHasRequired::class,
        'array2' => 'array_' . SchemaNestedHasRequired::class,
        'array3' => 'array',
        'mixed' => 'mixed',
        'mixed2' => 'mixed',
        'nullable' => 'string',
        'nullable2' => 'mixed',
    ]);
});

test('isPropertyExist', function () {
    expect($this->classInfo->isPropertyExist('xyz'))->toBeFalse()
        ->and($this->classInfo->isPropertyExist('string'))->toBeTrue();
});

test('getPropertyObjectSchemaType', function () {
    expect($this->classInfo->getPropertyObjectSchemaType('string'))->toBeNull()
        ->and($this->classInfo->getPropertyObjectSchemaType('object'))->toBe(SchemaNestedHasRequired::class);
});

test('getPropertyArrayItemSchemaType', function () {
    expect($this->classInfo->getPropertyArrayItemSchemaType('string'))->toBeNull()
        ->and($this->classInfo->getPropertyArrayItemSchemaType('array'))->toBe(SchemaNestedHasRequired::class);
});

test('getNestedPropertyTypes', function () {
    expect($this->classInfoNested->getNestedPropertyTypes())->toBe([
        'object' => [
            'required' => false,
            'nullable' => false,
            'types' => 'object',
        ],
        'object.string' => [
            'required' => false,
            'nullable' => false,
            'types' => 'string',
        ],
        'object.int' => [
            'required' => false,
            'nullable' => false,
            'types' => 'integer',
        ],
    ]);
});

test('getLaravelValidationRules', function () {
    expect($this->classInfo->getLaravelValidationRules())->toBe([
        'string' => ['required', 'string'],
        'string2' => ['string'],
        'int' => ['integer'],
        'int2' => ['integer'],
        'bool' => ['required', 'boolean'],
        'bool2' => ['boolean'],
        'float' => ['numeric'],
        'number' => ['numeric'],
        'union' => [],
        'union2' => [],

        'object' => ['array'],
        'object.string' => ['string'],
        'object.int' => ['required', 'integer'],
        'object2' => ['array'],
        'object2.string' => ['string'],
        'object2.int' => ['required', 'integer'],
        'object3' => ['array'],
        'object3.string' => ['string'],
        'object3.int' => ['integer'],
        'object3.nested' => ['array'],
        'object3.nested.string' => ['string'],
        'object3.nested.int' => ['integer'],

        'array' => ['array'],
        'array.*.string' => ['string'],
        'array.*.int' => ['required', 'integer'],
        'array2' => ['array'],
        'array2.*.string' => ['string'],
        'array2.*.int' => ['required', 'integer'],
        'array3' => ['array'],

        'mixed' => ['nullable'],
        'mixed2' => [],

        'nullable' => ['nullable', 'string'],
        'nullable2' => ['nullable'],
    ]);
});

test('getLaravelValidationRules with extra', function () {
    $callable = function () {};
    expect($this->classInfoSimple->getLaravelValidationRules([
        'string' => $callable, // callback 形式
        'string2' => 'required|size:10', // string 形式
        'bool2' => ['required', 'min:0'], // 数组形式
        'bool' => 'required', // 重复，去重
    ]))->toBe([
        'string' => ['required', 'string', $callable],
        'string2' => ['string', 'required', 'size:10'],
        'bool' => ['required', 'boolean'],
        'bool2' => ['boolean', 'required', 'min:0'],
    ]);
});
