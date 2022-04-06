<?php

declare(strict_types=1);

namespace Ekok\Config;

use Ekok\Utils\Arr;
use Ekok\Utils\Str;
use Ekok\Utils\Call;
use Ekok\Utils\File;
use Ekok\Cache\Cache;
use Ekok\Container\Di;
use Ekok\Router\Router;
use Ekok\EventDispatcher\Dispatcher;
use Ekok\Config\Attribute\Route as AttributeRoute;
use Ekok\EventDispatcher\EventSubscriberInterface;
use Ekok\Config\Attribute\Service as AttributeService;
use Ekok\Config\Attribute\Subscribe as AttributeSubscribe;

class Configurator
{
    public function __construct(
        private Di $di,
        private Dispatcher $dispatcher,
        private Router $router,
        private Cache $cache,
    ) {}

    public function getClassByScan(string $directory): array
    {
        return $this->cache->get($key = $directory . '.cls') ?? (
            $this->cache->set(
                $key,
                $classes = File::getClassByScan($directory),
                null,
                $saved,
            ) && $saved ? $classes : array()
        );
    }

    public function loadSubscribers(string ...$directories): static
    {
        return $this->runLoader('loadSubscriber', $directories);
    }

    public function loadSubscriber(string|object $class): static
    {
        if (is_string($class) && is_subclass_of($class, EventSubscriberInterface::class)) {
            $this->dispatcher->addSubscriber($class);

            return $this;
        }

        return $this->doLoad(
            $class,
            static function (\ReflectionClass $ref) {
                $attrs = $ref->getAttributes(AttributeSubscribe::class);

                /** @var AttributeSubscribe|null */
                $attr = $attrs ? $attrs[0]->newInstance() : null;

                return array($attr?->listens ?? array());
            },
            function (\ReflectionMethod $ref, $listens) use ($class) {
                $handler = Call::standarize($class, $ref->name);

                if (!$attrs = $ref->getAttributes(AttributeSubscribe::class)) {
                    if (Str::equals($ref->name, ...$listens)) {
                        $this->dispatcher->on($ref->name, $handler);
                    }

                    return;
                }

                /** @var AttributeSubscribe */
                $subscriber = $attrs[0]->newInstance();

                Arr::walk(
                    $subscriber->listens ?? array($ref->name),
                    fn (string $event) => $this->dispatcher->on($event, $handler),
                );
            },
        );
    }

    public function loadRoutes(string ...$directories): static
    {
        return $this->runLoader('loadRoute', $directories);
    }

    public function loadRoute(string|object $class): static
    {
        return $this->doLoad(
            $class,
            static function (\ReflectionClass $ref) {
                $attrs = $ref->getAttributes(AttributeRoute::class);
                $group = array(
                    'path' => '/',
                    'name' => null,
                    'verbs' => 'GET',
                    'attrs' => array(),
                );

                if ($attrs) {
                    /** @var AttributeRoute */
                    $attr = $attrs[0]->newInstance();

                    $group = array(
                        'path' => rtrim($attr->path ?? '', '/') . '/',
                        'name' => $attr->name,
                        'verbs' => $attr->verbs ?? 'GET',
                        'attrs' => $attr->attrs ?? array(),
                    );
                }

                return array($group);
            },
            function (\ReflectionMethod $ref, $group) use ($class) {
                $attrs = $ref->getAttributes(AttributeRoute::class);

                if (!$attrs) {
                    return;
                }

                /** @var AttributeRoute */
                $route = $attrs[0]->newInstance();

                $definition = $this->routeBuildAttr($route, $group);
                $handler = Call::standarize($class, $ref->name);

                $this->router->route($definition, $handler);
            },
        );
    }

    public function loadServices(string ...$directories): static
    {
        return $this->runLoader('loadService', $directories);
    }

    public function loadService(string $class): static
    {
        return $this->doLoad(
            $class,
            function (\ReflectionClass $ref) {
                Arr::walk(
                    $ref->getAttributes(AttributeService::class),
                    function (\ReflectionAttribute $attribute) use ($ref) {
                        /** @var AttributeService */
                        $attr = $attribute->newInstance();

                        $this->di->addRule($attr->name ?? $ref->name, array(
                            'class' => $ref->name,
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
            },
        );
    }

    private function runLoader(string $load, array $directories): static
    {
        Arr::walk(
            $directories,
            fn (string $directory) => Arr::walk(
                $this->getClassByScan($directory),
                fn (string $class) => $this->$load($class),
            ),
        );

        return $this;
    }

    private function doLoad(
        string|object $class,
        \Closure $onClass,
        \Closure $onMethod = null,
    ): static {
        $ref = new \ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            return $this;
        }

        $args = $onClass ? (array) $onClass($ref) : array();

        if ($onMethod) {
            Arr::walk(
                $ref->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn (\ReflectionMethod $ref) => $onMethod($ref, ...$args),
            );
        }

        return $this;
    }

    private function routeBuildAttr(AttributeRoute $route, array $group): string
    {
        $definition = $route->verbs ?? $group['verbs'];
        $attrs = array_merge($group['attrs'], $route->attrs ?? array());

        if ($route->name) {
            $definition .= ' @' . $group['name'] . $route->name;
        }

        if ($route->path) {
            $definition .= ' ' . $group['path'] . ltrim($route->path, '/');
        }

        if ($attrs) {
            $line = Arr::reduce(
                $attrs,
                static function ($attrs, $value, $tag) {
                    if ($attrs) {
                        $attrs .= ',';
                    }

                    if (is_numeric($tag)) {
                        $attrs .= is_array($value) ? implode(',', $value) : $value;
                    } elseif (is_array($value)) {
                        $attrs .= $tag . '=' . implode(';', $value);
                    } else {
                        $attrs .= $tag . '=' . $value;
                    }

                    return $attrs;
                },
            );

            $definition .= ' [' . $line . ']';
        }

        return $definition;
    }
}
