<?php

namespace WebmanTech\Swagger\Controller\RequiredElementsAnnotations\PathItem;

use OpenApi\Annotations as OA;

class OpenapiSpec
{
    /**
     * Zero configuration example
     *
     * Will be deleted automatically when adding a new api interface
     *
     * @OA\Get(
     *     path="/example",
     *     @OA\Response(
     *         response="200",
     *         description="The data"
     *     )
     * )
     */
    public function example()
    {
    }
}