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
                $handler = Call::standarize($class->name, $method->name, $method->isStatic());

                if (!$attrs = $method->getAttributes(Subscribe::class)) {
                    if (isset($listens[$method->name])) {
                        $this->dispatcher->on($listens[$method->name], $handler);
                    } elseif (Str::equals($method->name, ...$events)) {
                        $this->dispatcher->on($method->name, $handler);
                    }

                    return;
                }

                /** @var Subscribe */
                $subscriber = $attrs[0]->newInstance();
                $registers = $subscriber->listens ?? array($method->name);

                array_walk(
                    $registers,
                    fn (string $event) => $this->dispatcher->on($event, $handler),
                );
            },
        );
    }
}
