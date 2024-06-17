<?php

use Tests\Fixtures\SchemaExampleTypes;
use Tests\Fixtures\SchemaNested;
use Tests\Fixtures\SchemaNestedHasRequired;
use Tests\Fixtures\SchemaNestedWithNested;
use WebmanTech\Swagger\SchemaAnnotation\DTO\ClassInfoDTO;
use WebmanTech\Swagger\SchemaAnnotation\ReflectionClassReader;

test('read', function () {
    $data = ReflectionClassReader::read(SchemaExampleTypes::class);
    expect($data->toArray())->toBe((new ClassInfoDTO([
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
            'array' => 'array_' . SchemaNested::class,
            'array2' => 'array_' . SchemaNested::class,
            'array3' => 'array',
            'mixed' => 'mixed',
            'mixed2' => 'mixed',
            'nullable' => 'string',
            'nullable2' => 'mixed',
        ],
    ]))->toArray());
});
