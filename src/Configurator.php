<?php

declare(strict_types=1);

namespace Ekok\Config;

use Ekok\Utils\File;
use Ekok\Cache\Cache;
use Ekok\Container\Di;
use Ekok\Config\Loader\LoaderInterface;
use Ekok\Config\Loader\RouteLoader;
use Ekok\Config\Loader\ServiceLoader;
use Ekok\Config\Loader\SubscriberLoader;
use Ekok\Utils\Arr;

class Configurator
{
    public function __construct(
        private Di $di,
        private Cache $cache,
        private RouteLoader $routeLoader,
        private ServiceLoader $serviceLoader,
        private SubscriberLoader $subscriberLoader,
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
        return $this->runLoader($this->subscriberLoader, $directories);
    }

    public function loadRoutes(string ...$directories): static
    {
        return $this->runLoader($this->routeLoader, $directories);
    }

    public function loadServices(string ...$directories): static
    {
        return $this->runLoader($this->serviceLoader, $directories);
    }

    private function runLoader(LoaderInterface $loader, array $directories): static
    {
        array_walk(
            $directories,
            fn (string $directory) => Arr::walk(
                $this->getClassByScan($directory),
                static fn (string $class) => $loader->loadClass($class),
            ),
        );

        return $this;
    }
}
