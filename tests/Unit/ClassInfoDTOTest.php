<?php

namespace Unit;

use Tests\Fixtures\SchemaNested;
use WebmanTech\Swagger\SchemaAnnotation\DTO\ClassInfoDTO;

beforeEach(function () {
    $this->classInfo = new ClassInfoDTO([
        'required' => ['string', 'bool'],
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
            'object' => 'object_' . SchemaNested::class,
            'object2' => 'object_' . SchemaNested::class,
            'array' => 'array_' . SchemaNested::class,
            'array2' => 'array_' . SchemaNested::class,
            'array3' => 'array',
            'mixed' => 'mixed',
            'mixed2' => 'mixed',
            'nullable' => 'string',
        ],
    ]);
});

test('required', function () {
    expect($this->classInfo->required)->toBe(['string', 'bool']);
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
        'object' => 'object_' . SchemaNested::class,
        'object2' => 'object_' . SchemaNested::class,
        'array' => 'array_' . SchemaNested::class,
        'array2' => 'array_' . SchemaNested::class,
        'array3' => 'array',
        'mixed' => 'mixed',
        'mixed2' => 'mixed',
        'nullable' => 'string',
    ]);
});

test('isPropertyExist', function () {
    expect($this->classInfo->isPropertyExist('xyz'))->toBeFalse()
        ->and($this->classInfo->isPropertyExist('string'))->toBeTrue();
});

test('getPropertyObjectSchemaType', function () {
    expect($this->classInfo->getPropertyObjectSchemaType('string'))->toBeNull()
        ->and($this->classInfo->getPropertyObjectSchemaType('object'))->toBe(SchemaNested::class);
});

test('getPropertyArrayItemSchemaType', function () {
    expect($this->classInfo->getPropertyArrayItemSchemaType('string'))->toBeNull()
        ->and($this->classInfo->getPropertyArrayItemSchemaType('array'))->toBe(SchemaNested::class);
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
        'union' => ['string', 'integer'], // TODO 可能不能验证
        'union2' => ['string', 'integer'], // TODO 可能不能验证

        'object' => ['array'],
        'object.string' => ['string'],
        'object.int' => ['required', 'integer'],
        'object2' => ['array'],
        'object2.string' => ['string'],
        'object2.int' => ['required', 'integer'],

        'array' => ['array'],
        'array.*.string' => ['string'],
        'array.*.int' => ['required', 'integer'],
        'array2' => ['array'],
        'array2.*.string' => ['string'],
        'array2.*.int' => ['required', 'integer'],
        'array3' => ['array'],

        'mixed' => ['mixed'], // TODO 可能不能验证
        'mixed2' => ['mixed'], // TODO 可能不能验证

        'nullable' => ['string'],
    ]);
});
