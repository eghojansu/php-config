<?php

declare(strict_types=1);

namespace Ekok\Config\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Rest
{
    public function __construct(
        public string $name,
        public string|null $prefix = null,
        public array|null $attrs = null,
    ) {}
}
