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

namespace Apisearch\Plugin\SearchesMachine\Domain\Middleware;

use Apisearch\Plugin\SearchesMachine\Console\ProcessSearchMachineCommand;
use Apisearch\Server\Domain\Model\CrontabLine;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\GetCrontab;
use React\Promise\PromiseInterface;

/**
 * Class GetProcessingCrontabMiddleware.
 */
class GetProcessingCrontabMiddleware implements PluginMiddleware
{
    private int $minutesIntervalBetweenProcessing;

    /**
     * @param int $minutesIntervalBetweenProcessing
     */
    public function __construct(int $minutesIntervalBetweenProcessing)
    {
        $this->minutesIntervalBetweenProcessing = $minutesIntervalBetweenProcessing;
    }

    /**
     * @param GetCrontab $command
     * @param callable   $next
     *
     * @return PromiseInterface
     */
    public function execute($command, $next): PromiseInterface
    {
        $command->addLine(CrontabLine::fromMinutesInterval(
            $this->minutesIntervalBetweenProcessing,
            'php bin/console '.ProcessSearchMachineCommand::NAME
        ));

        return $next($command);
    }

    public function onlyHandle(): array
    {
        return [
            GetCrontab::class,
        ];
    }
}
