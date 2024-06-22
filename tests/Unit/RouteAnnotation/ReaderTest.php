<?php

use WebmanTech\Swagger\RouteAnnotation\DTO\RouteConfigDTO;
use WebmanTech\Swagger\RouteAnnotation\Reader;

test('getData', function () {
    $reader = new Reader();
    $data = $reader->getData(get_path('/Fixtures/RouteAnnotation/ExampleAttribution'));
    $data = array_map(fn(RouteConfigDTO $item) => $item->toArray(), $data);
    $excepted = require get_path('/Fixtures/RouteAnnotation/ExampleAttribution/controller/excepted_config.php');

    expect($data)->toBe($excepted);
});
