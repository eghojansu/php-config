<?php

use Ekok\Cache\Cache;
use Ekok\Container\Di;
use Ekok\Router\Router;
use Ekok\Config\Configurator;
use Ekok\EventDispatcher\Event;
use Ekok\EventDispatcher\Dispatcher;

class ConfiguratorTest extends \Codeception\Test\Unit
{
    /** @var Configurator */
    private $config;

    /** @var Di */
    private $container;

    /** @var Dispatcher */
    private $dispatcher;

    /** @var Router */
    private $router;

    protected function _before()
    {
        $this->container = new Di(array(
            Dispatcher::class => array('shared' => true),
            Configurator::class => array('shared' => true),
            Router::class => array('shared' => true),
            Cache::class => array('shared' => true),
        ));

        $this->config = $this->container->make(Configurator::class);
        $this->dispatcher = $this->container->make(Dispatcher::class);
        $this->router = $this->container->make(Router::class);
    }

    public function testLoadSubscribers()
    {
        $this->config->loadSubscribers(TEST_DATA . '/classes/Subscriber');

        $this->dispatcher->dispatch($event = Event::named('onFoo'));
        $this->assertTrue($event->isPropagationStopped());

        $this->dispatcher->dispatch($event = Event::named('onBar'));
        $this->assertTrue($event->isPropagationStopped());

        $this->dispatcher->dispatch($event = Event::named('onBaz'));
        $this->assertTrue($event->isPropagationStopped());

        $this->dispatcher->dispatch($event = Event::named('me'));
        $this->assertTrue($event->isPropagationStopped());

        $this->dispatcher->dispatch($event = Event::named('onQux'));
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testLoadRoutes()
    {
        $this->config->loadRoutes(TEST_DATA . '/classes/Controller');

        $routes = $this->router->getRoutes();
        $aliases = $this->router->getAliases();

        $this->assertCount(3, $routes);
        $this->assertCount(1, $aliases);
        $this->assertSame('\AController@home', $routes['/a/home']['GET']['handler']);
        $this->assertSame('\BController@home', $routes['/b']['GET']['handler']);

        $expected = array(
            'handler' => '\BController@complexAttributes',
            'alias' => null,
            'tags' => array(
                'this', 'is', 'a', 'bunch', 'of', 'tags',
            ),
            'named-tag' => 'foo',
            'named-tags' => array('foo', 'bar'),
        );
        $this->assertEquals($expected, $routes['/b/complex']['GET']);
    }

    public function testLoadRoute()
    {
        $this->config->loadRoute(new AController());

        $routes = $this->router->getRoutes();
        $aliases = $this->router->getAliases();

        $this->assertCount(1, $routes);
        $this->assertCount(0, $aliases);
    }

    public function testLoadServices()
    {
        $this->config->loadServices(TEST_DATA . '/classes/Service');

        /** @var FooService */
        $foo = $this->container->make('FooService');

        /** @var FooService */
        $foo2 = $this->container->make('FooService');

        $this->assertInstanceOf('FooService', $foo);
        $this->assertSame($foo, $foo2);
        $this->assertSame($foo->time, $foo2->time);

        $std = $this->container->make('stdClass');
        $std2 = $this->container->make('stdClass');

        $this->assertInstanceOf('stdClass', $std);
        $this->assertSame($std, $std2);

        $date = $this->container->make('DateTime');
        $date2 = $this->container->make('DateTime');

        $this->assertInstanceOf('DateTime', $date);
        $this->assertSame($date, $date2);
    }
}
