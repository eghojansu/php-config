<?php

use Ekok\Config\Attribute\Service;

#[Service()]
class FooService
{
    public $time;

    public function __construct()
    {
        $this->time = microtime(true);
    }
}
