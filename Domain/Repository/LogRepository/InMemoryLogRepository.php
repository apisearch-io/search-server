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

namespace Apisearch\Server\Domain\Repository\LogRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\ResetableRepository;
use DateTime;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class InMemoryLogRepository.
 */
class InMemoryLogRepository implements TemporaryLogRepository, ResetableRepository
{
    /**
     * @var Log[]
     */
    private array $logs = [];

    /**
     * @param RepositoryReference $repositoryReference
     * @param DateTime            $when
     * @param int                 $n
     * @param string              $type
     * @param array               $params
     *
     * @return PromiseInterface
     */
    public function log(
        RepositoryReference $repositoryReference,
        DateTime $when,
        int $n,
        string $type,
        array $params
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve();
        }

        $indexUUID = $repositoryReference->getIndexUUID();
        $indexUUIDComposed = $indexUUID instanceof IndexUUID ? $indexUUID->composeUUID() : null;
        $appUUIDComposed = $appUUID->composeUUID();
        $this->logs[] = new Log(
            $appUUIDComposed,
            $indexUUIDComposed,
            $when,
            $n,
            $type,
            $params
        );

        return resolve();
    }

    /**
     * @param LogFilter $filter
     *
     * @return PromiseInterface<Log[]>
     */
    public function getLogs(LogFilter $filter): PromiseInterface
    {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $logs = \array_map(
            function (Log $log) {
                return LogWithText::createFromLog($log);
            },
            \array_filter($this->logs, function (Log $log) use ($filter) {
                return $this->logIsValidFromFilter($log, $filter);
            })
        );

        if (!empty($filter->getPagination())) {
            list($limit, $page) = $filter->getPagination();
            $offset = $limit * ($page - 1);
            $logs = \array_slice($logs, $offset, $limit);
        }

        return resolve(\array_values($logs));
    }

    /**
     * @param Log       $log
     * @param LogFilter $filter
     *
     * @return bool
     */
    private function logIsValidFromFilter(
        Log $log,
        LogFilter $filter
    ): bool {
        $whenFormatted = $log->getWhen()->format('YmdHis');
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (
            (
                $appUUID->composeUUID() !== $log->getAppUUID()
            ) ||
            (
                !\is_null($indexUUID) &&
                '' !== $indexUUID->composeUUID() &&
                '*' !== $indexUUID->composeUUID() &&
                $indexUUID->composeUUID() !== $log->getIndexUUID()
            )
        ) {
            return false;
        }

        if (
            !\is_null($filter->getFrom()) &&
            $whenFormatted < $filter->getFrom()->format('YmdHis')
        ) {
            return false;
        }

        if (
            !\is_null($filter->getTo()) &&
            $whenFormatted >= $filter->getTo()->format('YmdHis')
        ) {
            return false;
        }

        if (
            !empty($filter->getTypes()) &&
            !\in_array($log->getType(), $filter->getTypes())
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return Log[]
     */
    public function getAndResetLogs(): array
    {
        $logs = $this->logs;
        $this->reset();

        return $logs;
    }

    /**
     * @return PromiseInterface
     */
    public function reset(): PromiseInterface
    {
        $this->logs = [];

        return resolve();
    }
}
