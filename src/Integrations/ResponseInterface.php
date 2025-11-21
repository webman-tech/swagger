<?php

namespace WebmanTech\Swagger\Integrations;

interface ResponseInterface
{
    /**
     * 响应 body 数据
     */
    public function body(string $content, array $header): mixed;
}
