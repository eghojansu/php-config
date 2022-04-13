<?php

use Ekok\Config\Attribute\Route;

#[Route('/c')]
class CController
{
    #[Route('')]
    public function home()
    {
        return 'Welcome home';
    }

    #[Route('foo')]
    public function foo()
    {
        return 'Welcome foo';
    }
}
