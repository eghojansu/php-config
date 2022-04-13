<?php

use Ekok\Config\Attribute\Rule;

class Bar
{
    #[Rule()]
    public function isTrueBar($value)
    {
        return 'true_bar' === $value;
    }
}
