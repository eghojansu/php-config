<?php

declare(strict_types=1);

namespace Ekok\Config\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Factory
{
    public function __construct(
        public string $class,
        public string|null $name = null,
        public bool $shared = true,
        public string|bool|null $alias = null,
        public bool $inherit = false,
        public array|null $tags = null,
    ) {}
}
