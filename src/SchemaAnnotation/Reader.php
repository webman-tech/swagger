<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

class Reader
{
    private array $reflections = [];

    public function read($class): Getter
    {
        if (!isset($this->reflections[$class])) {
            $this->reflections[$class] = new Getter(new \ReflectionClass($class));
        }
        return $this->reflections[$class];
    }
}
