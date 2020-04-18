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

namespace Apisearch\Server\Console;

use Clue\React\Block;
use Drift\EventBus\Bus\EventBus;
use React\EventLoop\LoopInterface;

/**
 * Class CommandWithEventBusAndGodToken.
 */
abstract class CommandWithEventBusAndGodToken extends ApisearchServerCommand
{
    /**
     * @var EventBus
     *
     * Event bus
     */
    protected $eventBus;

    /**
     * Controller constructor.
     *
     * @param EventBus      $eventBus
     * @param LoopInterface $loop
     * @param string        $godToken
     */
    public function __construct(
        EventBus $eventBus,
        LoopInterface $loop,
        string $godToken
    ) {
        parent::__construct($loop, $godToken);

        $this->eventBus = $eventBus;
    }

    /**
     * Dispatch event.
     *
     * @param object $event
     *
     * @return mixed
     */
    public function dispatchAndWait($event)
    {
        $promise = $this
            ->eventBus
            ->dispatch($event);

        return Block\await($promise, $this->loop);
    }
}
