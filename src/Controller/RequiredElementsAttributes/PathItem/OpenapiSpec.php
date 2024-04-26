<?php

namespace WebmanTech\Swagger\Controller\RequiredElementsAttributes\PathItem;

use OpenApi\Attributes as OA;

class OpenapiSpec
{
    #[OA\Get(
        path: '/example',
        summary: 'Zero configuration example',
        description: 'Will be deleted automatically when adding a new api interface',
        responses: [
            new OA\Response(response: 200, description: 'The data')
        ],
    )]
    public function example()
    {
    }
}