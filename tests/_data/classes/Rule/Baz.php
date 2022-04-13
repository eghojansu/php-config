<?php

use Ekok\Validation\Rule;

class Baz extends Rule
{
    protected function doValidate($value)
    {
        return 'baz' === $value;
    }
}
