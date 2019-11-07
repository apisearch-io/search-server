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

namespace Apisearch\Server\Domain\CommandHandler;

use Apisearch\Server\Domain\Command\PauseConsumers;
use Apisearch\Server\Domain\Consumer\ConsumerManager;
use React\Promise\PromiseInterface;

/**
 * Class PauseConsumersHandler.
 */
class PauseConsumersHandler
{
    /**
     * @var ConsumerManager
     *
     * Consumer manager
     */
    private $consumerManager;

    /**
     * ResumeConsumersHandler constructor.
     *
     * @param ConsumerManager $consumerManager
     */
    public function __construct(ConsumerManager $consumerManager)
    {
        $this->consumerManager = $consumerManager;
    }

    /**
     * Optimize the index.
     *
     * @param PauseConsumers $pauseConsumers
     *
     * @return PromiseInterface
     */
    public function handle(PauseConsumers $pauseConsumers): PromiseInterface
    {
        return $this
            ->consumerManager
            ->pauseConsumers($pauseConsumers->getTypes());
    }
}
