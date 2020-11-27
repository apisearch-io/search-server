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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Server\Domain\Query\GetLogs;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use React\Promise\PromiseInterface;

/**
 * Class GetLogsHandler.
 */
class GetLogsHandler
{
    private LogRepository $logsRepository;

    /**
     * @param LogRepository $logsRepository
     */
    public function __construct(LogRepository $logsRepository)
    {
        $this->logsRepository = $logsRepository;
    }

    /**
     * @param GetLogs $getLogs
     *
     * @return PromiseInterface
     */
    public function handle(GetLogs $getLogs): PromiseInterface
    {
        return $this
            ->logsRepository
            ->getLogs($getLogs->getLogFilter());
    }
}
