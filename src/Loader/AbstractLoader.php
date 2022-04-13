<?php

declare(strict_types=1);

namespace Ekok\Config\Loader;

class AbstractLoader implements LoaderInterface
{
    public function loadClass(string|object $class): void
    {
        $ref = new \ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            return;
        }

        $calls = array_filter(
            (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PROTECTED),
            static fn (\ReflectionMethod $method) => str_starts_with($method->name, '__load'),
        );
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        array_walk($calls, fn ($call) => $this->{$call->name}($ref, $methods));
    }

    protected static function getAttributeInstance(string $name, \ReflectionClass|\ReflectionMethod $ref): object|null
    {
        $attrs = $ref->getAttributes($name);

        return $attrs ? $attrs[0]->newInstance() : null;
    }

    protected static function runMethodsOnAttribute(string $name, array $methods, \Closure $cb): void
    {
        array_walk(
            $methods,
            fn (\ReflectionMethod $method) => (
                ($attrs = $method->getAttributes($name)) ? $cb(
                    $attrs[0]->newInstance(),
                    $method,
                ) : null
            ),
        );
    }
}
