<?php

namespace WebmanTech\Swagger\Controller\RequiredElementsAttributes\PathItem;

use OpenApi\Attributes as OA;

class OpenapiSpec
{
    #[OA\Get(
        path: '/example',
        description: 'Will be deleted automatically when adding a new api interface',
        summary: 'Zero configuration example',
        responses: [
            new OA\Response(response: 200, description: 'The data')
        ],
    )]
    public function example()
    {
    }
}
