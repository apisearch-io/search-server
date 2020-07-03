<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Server\Tests\Functional;

use Drift\HttpKernel\Event\DomainEventEnvelope;

/**
 * Class EventCollector.
 */
final class EventCollector
{
    /**
     * @var int[]
     */
    private $events = [];

    /**
     * @param object $event
     */
    public function registerEvent($event)
    {
        $eventClass = $event instanceof DomainEventEnvelope
            ? \get_class($event->getDomainEvent())
            : \get_class($event);

        if (!\array_key_exists($eventClass, $this->events)) {
            $this->events[$eventClass] = 0;
        }

        ++$this->events[$eventClass];
    }

    /**
     * @param string $eventClass
     *
     * @return int
     */
    public function count(string $eventClass): int
    {
        return $this->events[$eventClass] ?? 0;
    }
}
