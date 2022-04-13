<?php

declare(strict_types=1);

namespace Ekok\Config\Loader;

interface LoaderInterface
{
    public function loadClass(string|object $class): void;
}
