<?php

declare(strict_types=1);

namespace Ekok\Config\Loader;

use Ekok\Utils\Arr;
use Ekok\Utils\Call;
use Ekok\Router\Router;
use Ekok\Config\Attribute\Rest;
use Ekok\Config\Attribute\Route;
use Ekok\Config\Attribute\Resource;

class RouteLoader extends AbstractLoader
{
    public function __construct(private Router $router)
    {}

    protected function __loadRoutes(\ReflectionClass $class, array $methods): void
    {
        $group = self::routeGroup(self::getAttributeInstance(Route::class, $class));

        self::runMethodsOnAttribute(Route::class, $methods, function (Route $route, \ReflectionMethod $method) use ($class, $group) {
            $definition = self::routeBuildAttr($route, $group);
            $handler = Call::standarize($class->name, $method->name, $method->isStatic());

            $this->router->route($definition, $handler);
        });
    }

    protected function __loadRests(\ReflectionClass $class): void
    {
        /** @var Rest */
        $rest = self::getAttributeInstance(Rest::class, $class);

        if ($rest) {
            $this->router->rest($rest->name, $class->name, $rest->prefix, $rest->attrs);
        }
    }

    protected function __loadResources(\ReflectionClass $class): void
    {
        /** @var Resource */
        $resource = self::getAttributeInstance(Resource::class, $class);

        if ($resource) {
            $this->router->resource($resource->name, $class->name, $resource->prefix, $resource->attrs);
        }
    }

    private static function routeGroup(Route|null $route): array
    {
        if ($route) {
            return array(
                'path' => rtrim($route->path ?? '', '/'),
                'name' => $route->name,
                'verbs' => $route->verbs ?? 'GET',
                'attrs' => $route->attrs ?? array(),
            );
        }

        return array(
            'path' => null,
            'name' => null,
            'verbs' => 'GET',
            'attrs' => array(),
        );
    }

    private static function routeBuildAttr(Route $route, array $group): string
    {
        $definition = $route->verbs ?? $group['verbs'];
        $attrs = array_merge($group['attrs'], $route->attrs ?? array());

        if ($route->name) {
            $definition .= ' @' . $group['name'] . $route->name;
        }

        if (null !== $route->path) {
            $definition .= ' ' . $group['path'] . ($route->path ? '/' . ltrim($route->path, '/') : null);
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
