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

namespace Apisearch\Server\Domain\EventPublisher;

use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use React\Promise\PromiseInterface;

/**
 * Interface EventPublisher.
 */
interface EventPublisher
{
    /**
     * Publish event.
     *
     * @param DomainEventWithRepositoryReference $domainEventWithRepositoryReference
     *
     * @return PromiseInterface
     */
    public function publish(DomainEventWithRepositoryReference $domainEventWithRepositoryReference): PromiseInterface;
}
