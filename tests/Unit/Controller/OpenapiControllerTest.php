<?php

use WebmanTech\Swagger\Controller\OpenapiController;

test('openapiDoc', function () {
    $controller = new OpenapiController();
    $response = $controller->openapiDoc([
        'scan_path' => get_path('/Fixtures/RouteAnnotation/ExampleAttribution/controller')
    ]);
    $doc = $response->rawBody();

    expect($response->rawBody())->toMatchSnapshot()
        ->and($response->getHeader('Content-Type'))->toBe('application/x-yaml');
});
