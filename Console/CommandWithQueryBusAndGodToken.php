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
use Drift\CommandBus\Bus\QueryBus;
use React\EventLoop\LoopInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CommandWithQueryBusAndGodToken.
 */
abstract class CommandWithQueryBusAndGodToken extends ApisearchServerCommand
{
    protected QueryBus $queryBus;

    /**
     * Controller constructor.
     *
     * @param QueryBus        $queryBus
     * @param LoopInterface   $loop
     * @param KernelInterface $kernel
     * @param string          $godToken
     */
    public function __construct(
        QueryBus $queryBus,
        LoopInterface $loop,
        KernelInterface $kernel,
        string $godToken
    ) {
        parent::__construct($loop, $kernel, $godToken);

        $this->queryBus = $queryBus;
    }

    /**
     * Ask query.
     *
     * @param object $query
     *
     * @return mixed
     */
    public function askAndWait($query)
    {
        $promise = $this
            ->queryBus
            ->ask($query);

        return Block\await($promise, $this->loop);
    }
}
