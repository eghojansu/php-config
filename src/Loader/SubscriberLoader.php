<?php

declare(strict_types=1);

namespace Ekok\Config\Loader;

use Ekok\Utils\Str;
use Ekok\Utils\Call;
use Ekok\Config\Attribute\Subscribe;
use Ekok\EventDispatcher\Dispatcher;
use Ekok\EventDispatcher\EventSubscriberInterface;

class SubscriberLoader extends AbstractLoader
{
    public function __construct(private Dispatcher $dispatcher)
    {}

    public function loadClass(string|object $class): void
    {
        if (is_string($class) && is_subclass_of($class, EventSubscriberInterface::class)) {
            $this->dispatcher->addSubscriber($class);

            return;
        }

        parent::loadClass($class);
    }

    protected function __loadSubscriber(\ReflectionClass $class, array $methods): void
    {
        /** @var Subscribe|null */
        $attr = self::getAttributeInstance(Subscribe::class, $class);
        $listens = $attr?->listens ?? array();
        $events = array_values($listens);

        array_walk(
            $methods,
            function (\ReflectionMethod $method) use ($class, $listens, $events) {
                /** @var Subscribe */
                $subscriber = self::getAttributeInstance(Subscribe::class, $method);
                $handler = Call::standarize($class->name, $method->name, $method->isStatic());
                $registers = match(true) {
                    !!$subscriber => $subscriber->listens ?? array($method->name),
                    isset($listens[$method->name]) => array($listens[$method->name]),
                    Str::equals($method->name, ...$events) => array($method->name),
                    default => array(),
                };

                array_walk(
                    $registers,
                    fn (string $event) => $this->dispatcher->on($event, $handler),
                );
            },
        );
    }
}
