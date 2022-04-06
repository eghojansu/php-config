<?php

use Ekok\Config\Attribute\Factory;

#[Factory('stdClass')]
class StdFactory
{
    public function __invoke()
    {
        return new stdClass();
    }
}
