<?php

namespace WebmanTech\Swagger\Integrations;

use WebmanTech\Swagger\DTO\ConfigHostForbiddenDTO;

interface MiddlewareInterface
{
    /**
     * 创建 HostForbiddenMiddleware 中间件
     */
    public function makeHostForbiddenMiddleware(ConfigHostForbiddenDTO $config): mixed;
}
