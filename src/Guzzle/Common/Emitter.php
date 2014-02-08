<?php

namespace Guzzle\Common;

/**
 * Guzzle event emitter.
 *
 * Some of this class is based on the Symfony EventDispatcher component, which ships with the following license:
 *
 *     This file is part of the Symfony package.
 *
 *     (c) Fabien Potencier <fabien@symfony.com>
 *
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 *
 * @link https://github.com/symfony/symfony/tree/master/src/Symfony/Component/EventDispatcher
 */
class Emitter implements EmitterInterface
{
    /** @var array */
    private $listeners = [];

    /** @var array */
    private $sorted = [];

    public function on($eventName, callable $listener, $priority = 0)
    {
        $this->listeners[$eventName][$priority][] = $listener;
        unset($this->sorted[$eventName]);
    }

    public function once($eventName, callable $listener, $priority = 0)
    {
        $onceListener = function (Event $event, $eventName) use (&$onceListener, $eventName, $listener, $priority) {
            $this->removeListener($eventName, $onceListener);
            call_user_func($listener, $event, $eventName);
        };

        $this->on($eventName, $onceListener, $priority);
    }

    public function removeListener($eventName, callable $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            if (false !== ($key = array_search($listener, $listeners, true))) {
                unset($this->listeners[$eventName][$priority][$key], $this->sorted[$eventName]);
            }
        }
    }

    public function getListeners($eventName = null)
    {
        // Return all events in a sorted priority order
        if ($eventName === null) {
            foreach (array_keys($this->listeners) as $eventName) {
                if (!isset($this->sorted[$eventName])) {
                    $this->getListeners($eventName);
                }
            }
            return $this->sorted;
        }

        // Return the listeners for a specific event, sorted in priority order
        if (!isset($this->sorted[$eventName])) {
            if (!isset($this->listeners[$eventName])) {
                return [];
            } else{
                krsort($this->listeners[$eventName]);
                $this->sorted[$eventName] = call_user_func_array(
                    'array_merge',
                    $this->listeners[$eventName]
                );
            }
        }

        return $this->sorted[$eventName];
    }

    public function emit($eventName, EventInterface $event)
    {
        if (isset($this->listeners[$eventName])) {
            foreach ($this->getListeners($eventName) as $listener) {
                call_user_func($listener, $event, $eventName);
                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }

        return $event;
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $listener) {
            $this->on(
                $eventName,
                array($subscriber, $listener[0]),
                isset($listener[1]) ? $listener[1] : 0
            );
        }
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $listener) {
            $this->removeListener($eventName, array($subscriber, $listener[0]));
        }
    }
}
