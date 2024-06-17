<?php

use Tests\Fixtures\SchemaExample1;
use Tests\Fixtures\SchemaNested;
use WebmanTech\Swagger\SchemaAnnotation\DTO\ClassInfoDTO;
use WebmanTech\Swagger\SchemaAnnotation\ReflectionClassReader;

test('read', function () {
    $data = ReflectionClassReader::read(SchemaExample1::class);
    expect($data->toArray())->toBe((new ClassInfoDTO([
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
    ]))->toArray());
/*
    expect($data)->toBe([
        'string' => ['required' => true, 'types' => ['string']],
        'string2' => ['required' => false, 'types' => ['string']],
        'int' => ['required' => false, 'types' => ['integer']],
        'int2' => ['required' => false, 'types' => ['integer']],
        'bool' => ['required' => true, 'types' => ['boolean']],
        'bool2' => ['required' => false, 'types' => ['boolean']],
        'float' => ['required' => false, 'types' => ['float']],
        'number' => ['required' => false, 'types' => ['number']],

        'union' => ['required' => false, 'types' => ['string', 'integer']],
        'union2' => ['required' => false, 'types' => ['string', 'integer']],

        'object' => ['required' => false, 'types' => ['object']],
        'object.string' => ['required' => false, 'types' => ['string']],
        'object.int' => ['required' => true, 'types' => ['integer']],
        'object2' => ['required' => false, 'types' => ['object']],
        'object2.string' => ['required' => false, 'types' => ['string']],
        'object2.int' => ['required' => true, 'types' => ['integer']],

        'array' => ['required' => false, 'types' => ['array']],
        'array.*.string' => ['required' => false, 'types' => ['string']],
        'array.*.int' => ['required' => true, 'types' => ['integer']],
        'array2' => ['required' => false, 'types' => ['array']],
        'array2.*.string' => ['required' => false, 'types' => ['string']],
        'array2.*.int' => ['required' => true, 'types' => ['integer']],
        'array3' => ['required' => false, 'types' => ['array']],

        'mixed' => ['required' => false, 'types' => ['mixed']],
        'mixed2' => ['required' => false, 'types' => ['mixed']],

        'nullable' => ['required' => false, 'types' => ['string']],
    ]);*/
});
