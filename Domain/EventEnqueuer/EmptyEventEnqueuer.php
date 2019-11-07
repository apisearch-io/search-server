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

namespace Apisearch\Server\Domain\EventEnqueuer;

use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Class EmptyEventEnqueuer.
 */
class EmptyEventEnqueuer implements EventEnqueuer
{
    /**
     * Enqueue a domain event.
     *
     * @param array $event
     *
     * @return PromiseInterface
     */
    public function enqueueEvent(array $event): PromiseInterface
    {
        return new FulfilledPromise();
    }
}
