<?php

use OpenApi\Attributes as OA;
use WebmanTech\Swagger\SchemaAnnotation\ReflectionClassReader;
use WebmanTech\Swagger\SchemaAnnotation\Validator;

#[OA\Schema(required: ['string', 'bool', 'object'])]
class Example1
{
    #[OA\Property]
    public string $string;
    #[OA\Property]
    public int $int;
    #[OA\Property]
    public float $float;
    #[OA\Property]
    public bool $bool;

    #[OA\Property]
    public string|int $union;

    #[OA\Property]
    public Nested $object;

    #[OA\Property(items: new OA\Items(ref: Nested::class))]
    public array $array;
}

#[OA\Schema(required: ['int'])]
class Nested
{
    #[OA\Property]
    public string $string;
    #[OA\Property]
    public int $int;
}

beforeEach(function () {
    $reader = new ReflectionClassReader();
    $factoryValidator = new \Illuminate\Validation\Factory(
        new \Illuminate\Translation\Translator(
            new \Illuminate\Translation\ArrayLoader(),
            ''
        ),
    );
    $this->validator = new Validator($reader, $factoryValidator);
});

test('Validator getRules', function () {
    $rules = $this->validator->getRules(Example1::class);
    expect($rules)->toBe([
        'string' => ['required', 'string'],
        'int' => ['integer'],
        'float' => ['numeric'],
        'bool' => ['required', 'boolean'],

        'union' => ['string', 'integer'],

        'object' => ['required', 'array'],
        'object.string' => ['string'],
        'object.int' => ['required', 'integer'],

        'array' => ['array'],
        'array.*.string' => ['string'],
        'array.*.int' => ['required', 'integer'],
    ]);
});

test('Validator getRules withExtra', function () {
    $rules = $this->validator->getRules(Nested::class, [
        'string' => ['json'],
    ]);
    expect($rules)->toBe([
        'string' => ['string', 'json'],
        'int' => ['required', 'integer'],
    ]);
});
