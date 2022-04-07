<?php

use Ekok\Config\Attribute\Subscribe;
use Ekok\EventDispatcher\Event;

#[Subscribe(array('onCustom' => 'custom'), 'onBar', 'onBaz')]
class BarSubscriber
{
    public function onBar(Event $event)
    {
        $event->stopPropagation();
    }

    public function onBaz(Event $event)
    {
        $event->stopPropagation();
    }

    #[Subscribe('me')]
    public function handleMe(Event $event)
    {
        $event->stopPropagation();
    }

    public function onCustom(Event $event)
    {
        $event->stopPropagation();
    }
}
