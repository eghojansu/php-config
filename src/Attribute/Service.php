<?php

declare(strict_types=1);

namespace Ekok\Config\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Service
{
    public function __construct(
        public string|null $name = null,
        public array|null $params = null,
        public bool $shared = true,
        public string|bool|null $alias = null,
        public array|null $substitutions = null,
        public array|null $calls = null,
        public bool $inherit = false,
        public array|null $tags = null,
    ) {}
}
