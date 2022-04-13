<?php

use Ekok\Config\Attribute\Rule;

#[Rule()]
class Foo
{
    public function __invoke($value)
    {
        return static::class === get_class($this) && 'foo' === $value;
    }
}
