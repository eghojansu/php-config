<?php

declare(strict_types=1);

namespace Ekok\Config\Loader;

use Ekok\Utils\Call;
use Ekok\Container\Di;
use Ekok\Config\Attribute\Factory;
use Ekok\Config\Attribute\Service;

class ServiceLoader extends AbstractLoader
{
    public function __construct(private Di $di)
    {}

    protected function __loadService(\ReflectionClass $class): void
    {
        $attributes = $class->getAttributes(Service::class);

        array_walk(
            $attributes,
            function (\ReflectionAttribute $attribute) use ($class) {
                /** @var Service */
                $attr = $attribute->newInstance();

                $this->di->addRule($attr->name ?? $class->name, array(
                    'class' => $class->name,
                    'shared' => $attr->shared,
                    'params' => $attr->params,
                    'alias' => $attr->alias,
                    'substitutions' => $attr->substitutions,
                    'calls' => $attr->calls,
                    'inherit' => $attr->inherit,
                    'tags' => $attr->tags,
                ));
            },
        );
    }

    protected function __loadFactory(\ReflectionClass $class, array $methods): void
    {
        $attributes = $class->getAttributes(Factory::class);

        array_walk(
            $attributes,
            function (\ReflectionAttribute $attribute) use ($class) {
                /** @var Service */
                $attr = $attribute->newInstance();

                $this->di->addRule($attr->name ?? $attr->class, array(
                    'create' => Call::standarize($class->name, '__invoke'),
                    'shared' => $attr->shared,
                    'alias' => $attr->alias,
                    'inherit' => $attr->inherit,
                    'tags' => $attr->tags,
                ));
            },
        );

        self::runMethodsOnAttribute(Factory::class, $methods, function (Factory $factory, \ReflectionMethod $method) use ($class) {
            $this->di->addRule($factory->name ?? $factory->class, array(
                'create' => Call::standarize($class->name, $method->name, $method->isStatic()),
                'shared' => $factory->shared,
                'alias' => $factory->alias,
                'inherit' => $factory->inherit,
                'tags' => $factory->tags,
            ));
        });
    }
}
