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

namespace Apisearch\Server\Http;

use Drift\EventBus\Bus\EventBus;
use Drift\EventBus\Exception\InvalidEventException;
use React\Promise\PromiseInterface;

/**
 * Class ControllerWithEventBus.
 */
abstract class ControllerWithEventBus extends BaseController
{
    private EventBus $eventBus;

    /**
     * Controller constructor.
     *
     * @param EventBus $eventBus
     */
    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    /**
     * @param object $event
     *
     * @return PromiseInterface
     *
     * @throws InvalidEventException
     */
    protected function dispatch($event): PromiseInterface
    {
        return $this
            ->eventBus
            ->dispatch($event);
    }
}
