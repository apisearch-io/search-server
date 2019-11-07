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

namespace Apisearch\Server\Domain\Plugin;

use Apisearch\Server\Domain\Model\CrontabLine;
use Apisearch\Server\Domain\Query\GetCrontab;
use React\Promise\PromiseInterface;

/**
 * Interface CrontabMiddleware.
 */
abstract class CrontabMiddleware implements PluginMiddleware
{
    /**
     * Execute middleware.
     *
     * @param GetCrontab $command
     * @param callable   $next
     *
     * @return PromiseInterface
     */
    public function execute(
        $command,
        $next
    ): PromiseInterface {
        foreach ($this->getCrontabLines() as $crontabLine) {
            $command->addLine($crontabLine);
        }

        return $next($command);
    }

    /**
     * Events subscribed namespace. Can refer to specific class namespace, any
     * parent class or any interface.
     *
     * By returning an empty array, means coupled to all.
     *
     * @return string[]
     */
    public function getSubscribedCommands(): array
    {
        return [GetCrontab::class];
    }

    /**
     * Get crontabs.
     *
     * @return CrontabLine[]
     */
    abstract protected function getCrontabLines(): array;
}
