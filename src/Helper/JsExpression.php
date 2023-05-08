<?php

namespace WebmanTech\Swagger\Helper;

class JsExpression
{
    private $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function __toString()
    {
        return $this->expression;
    }
}