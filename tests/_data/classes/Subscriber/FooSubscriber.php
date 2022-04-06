<?php

use Ekok\EventDispatcher\Event;
use Ekok\EventDispatcher\EventSubscriberInterface;

class FooSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return array(
            'onFoo',
        );
    }

    public function onFoo(Event $event)
    {
        $event->stopPropagation();
    }
}
