<?php

use Ekok\Config\Attribute\Factory;

class ServiceFactory
{
    #[Factory('DateTime')]
    public static function today()
    {
        return new \DateTime();
    }
}
