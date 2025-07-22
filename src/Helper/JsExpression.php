<?php

namespace WebmanTech\Swagger\Helper;

final readonly class JsExpression implements \Stringable
{
    public function __construct(private string $expression)
    {
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}
