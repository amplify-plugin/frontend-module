<?php

namespace Amplify\Frontend\Store;

use Exception;
use Traversable;


class AnalyticsBus implements \IteratorAggregate
{
    private static $instance;

    private array $events = [];

    protected function __construct() {}

    protected function __clone() {}

    public static function init(): static
    {
        if (! self::$instance) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public function put(string $event = null, array $payload =[])
    {
        if (empty($event)) {
            $this->events[] = $payload;

            return $this;
        }

        $this->events[$event] = $payload;

        return $this;
    }

    public function remove(string $event)
    {
        unset($this->events[$event]);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function get(string $event)
    {
        if (! isset($this->events[$event])) {
            throw new Exception("Event ({$event}) does not exist in Analytics");
        }

        return $this->events[$event];
    }

    public function all(): array
    {
        return $this->events;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->events);
    }
}
