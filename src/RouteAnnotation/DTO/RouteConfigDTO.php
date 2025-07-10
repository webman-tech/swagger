<?php

namespace WebmanTech\Swagger\RouteAnnotation\DTO;

final class RouteConfigDTO
{
    public function __construct(
        public string            $desc = '',
        public string            $method = '',
        public string            $path = '',
        public string            $controller = '',
        public string            $action = '',
        public null|string       $name = null,
        public null|string|array $middlewares = null,
    )
    {
    }
}
