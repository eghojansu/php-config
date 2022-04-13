<?php

declare(strict_types=1);

namespace Ekok\Config\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Rule
{
    public function __construct(
        public string|null $name = null,
        public string|null $message = null,
    ) {}
}
