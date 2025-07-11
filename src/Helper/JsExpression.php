<?php

namespace WebmanTech\Swagger\Helper;

final class JsExpression implements \Stringable
{
    public function __construct(private readonly string $expression)
    {
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}
