<?php

namespace WebmanTech\Swagger\Controller\RequiredElements\PathItem;

use OpenApi\Annotations as OA;

class OpenapiSpec
{
    /**
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