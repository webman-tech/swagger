<?php

namespace WebmanTech\Swagger\Integrations;

interface ResponseInterface
{
    /**
     * 渲染视图
     */
    public function renderView(string $view, array $data, string $viewPath): mixed;

    /**
     * 响应 body 数据
     */
    public function body(string $content, array $header): mixed;
}
