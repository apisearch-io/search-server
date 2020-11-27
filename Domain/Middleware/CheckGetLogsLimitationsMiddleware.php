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

namespace Apisearch\Server\Domain\Middleware;

use Apisearch\Server\Domain\Query\GetLogs;
use Drift\CommandBus\Middleware\DiscriminableMiddleware;

/**
 * Class CheckGetLogsLimitationsMiddleware.
 */
class CheckGetLogsLimitationsMiddleware implements DiscriminableMiddleware
{
    private int $numberOfLogsPerPageLimitation;

    /**
     * @param int $numberOfLogsPerPageLimitation
     */
    public function __construct(int $numberOfLogsPerPageLimitation)
    {
        $this->numberOfLogsPerPageLimitation = $numberOfLogsPerPageLimitation;
    }

    /**
     * @param GetLogs  $query
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($query, callable $next)
    {
        $logFilter = $query->getLogFilter();
        $pagination = $logFilter->getPagination();

        if (!empty($pagination)) {
            list($limit, $page) = $pagination;
            $limit = \min($limit, $this->numberOfLogsPerPageLimitation);
            $logFilter->paginate($limit, $page);
        } else {
            $logFilter->paginate($this->numberOfLogsPerPageLimitation, 1);
        }

        return $next($query);
    }

    /**
     * @return array
     */
    public function onlyHandle(): array
    {
        return [
            GetLogs::class,
        ];
    }
}
