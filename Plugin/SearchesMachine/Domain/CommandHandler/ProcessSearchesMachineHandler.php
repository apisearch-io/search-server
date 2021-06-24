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

namespace Apisearch\Plugin\SearchesMachine\Domain\CommandHandler;

use Apisearch\Plugin\SearchesMachine\Domain\Command\ProcessSearchesMachine;
use Apisearch\Plugin\SearchesMachine\Domain\Processor\SearchesMachineProcessor;
use React\Promise\PromiseInterface;

/**
 * Class ProcessSearchesMachineHandler.
 */
class ProcessSearchesMachineHandler
{
    private SearchesMachineProcessor $searchesMachineProcessor;

    /**
     * @param SearchesMachineProcessor $searchesMachineProcessor
     */
    public function __construct(SearchesMachineProcessor $searchesMachineProcessor)
    {
        $this->searchesMachineProcessor = $searchesMachineProcessor;
    }

    /**
     * @param ProcessSearchesMachine $processSearchMachine
     *
     * @return PromiseInterface
     */
    public function handle(ProcessSearchesMachine $processSearchMachine): PromiseInterface
    {
        return $this
            ->searchesMachineProcessor
            ->ingestAndProcessSearchesFromRedis()
            ->then(function (int $searchesFlushed) use ($processSearchMachine) {
                $processSearchMachine->totalFlushed = $searchesFlushed;
            });
    }
}
