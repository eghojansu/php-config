<?php

use Ekok\Config\Attribute\Route;

#[Route('/a')]
class AController
{
    #[Route('/home')]
    public function home()
    {
        return 'Welcome home';
    }

    public function thisMethodShouldBeSkippedFromLoading()
    {}
}
