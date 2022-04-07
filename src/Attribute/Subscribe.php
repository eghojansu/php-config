<?php

declare(strict_types=1);

namespace Ekok\Config\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Subscribe
{
    /** @var array|null */
    public $listens = null;

    public function __construct(string|array $events = null, string ...$eventNames)
    {
        $this->listens = array_merge((array) $events, $eventNames) ?: null;
    }
}
