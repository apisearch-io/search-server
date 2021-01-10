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

namespace Apisearch\Plugin\Admin\Domain\Middleware;

use Apisearch\Plugin\Admin\Console\PreloadAllMetricsCommand;
use Apisearch\Server\Domain\Model\CrontabLine;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\GetCrontab;
use React\Promise\PromiseInterface;

/**
 * Class GetPreloadAllMetricsCrontabMiddleware.
 */
class GetPreloadAllMetricsCrontabMiddleware implements PluginMiddleware
{
    private int $minutesIntervalBetweenPreloadAllMetrics;

    /**
     * @param int $minutesIntervalBetweenPreloadAllMetrics
     */
    public function __construct(int $minutesIntervalBetweenPreloadAllMetrics)
    {
        $this->minutesIntervalBetweenPreloadAllMetrics = $minutesIntervalBetweenPreloadAllMetrics;
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
            $this->minutesIntervalBetweenPreloadAllMetrics,
            'php bin/console '.PreloadAllMetricsCommand::NAME
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
