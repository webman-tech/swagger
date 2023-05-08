<?php

namespace WebmanTech\Swagger\Controller\RequiredElements\PathItem;

use OpenApi\Annotations as OA;

class OpenapiSpec
{
    /**
     * 零配置例子
     *
     * 会在添加新的 api 接口后自动删除
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