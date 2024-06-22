<?php

use WebmanTech\Swagger\Controller\OpenapiController;

test('openapiDoc', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => get_path('/Fixtures/RouteAnnotation/ExampleAttribution')
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/x-yaml');
});

test('openapiDoc use schema', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => get_path('/Fixtures/RouteAnnotation/ExampleSchema')
    ]);

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/x-yaml');
});
