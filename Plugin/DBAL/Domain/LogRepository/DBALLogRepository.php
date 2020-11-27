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

namespace Apisearch\Plugin\DBAL\Domain\LogRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\LogRepository\Log;
use Apisearch\Server\Domain\Repository\LogRepository\LogFilter;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\LogWithText;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class DBALLogRepository.
 */
final class DBALLogRepository implements LogRepository
{
    private Connection $connection;
    private string $tableName;

    /**
     * @param Connection $dbalPluginConnection
     * @param string     $logsTable
     */
    public function __construct(
        Connection $dbalPluginConnection,
        string $logsTable
    ) {
        $this->connection = $dbalPluginConnection;
        $this->tableName = $logsTable;
    }

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

        return $this
            ->connection
            ->insert($this->tableName, [
                'app_uuid' => $appUUIDComposed,
                'index_uuid' => $indexUUIDComposed,
                'time' => $when->format('YmdHis'),
                'n' => $n,
                'type' => $type,
                'params' => \json_encode($params),
            ]);
    }

    /**
     * @param LogFilter $filter
     *
     * @return PromiseInterface<LogWithText[]>
     */
    public function getLogs(LogFilter $filter): PromiseInterface
    {
        $queryBuilder = $this
            ->connection
            ->createQueryBuilder()
            ->select('*')
            ->orderBy('l.time', 'DESC')
            ->from($this->tableName, 'l');

        if (!empty($filter->getPagination())) {
            list($limit, $page) = $filter->getPagination();
            $queryBuilder->setMaxResults($limit);
            $queryBuilder->setFirstResult($limit * ($page - 1));
        }

        $this->applyFilterToQueryBuilder($queryBuilder, $filter);

        return $this
            ->connection
            ->query($queryBuilder)
            ->then(function (Result $result) {
                $logs = [];

                foreach ($result->fetchAllRows() as $row) {
                    $logs[] = LogWithText::createFromLog(new Log(
                        $row['app_uuid'],
                        $row['index_uuid'],
                        DateTime::createFromFormat('YmdHis', \strval($row['time'])),
                        \intval($row['n']),
                        $row['type'],
                        \json_decode($row['params'], true)
                    ));
                }

                return $logs;
            });
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param LogFilter    $filter
     */
    private function applyFilterToQueryBuilder(
        QueryBuilder $queryBuilder,
        LogFilter $filter
    ) {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (!\is_null($filter->getFrom())) {
            $queryBuilder->andWhere('l.time >= :from');
            $queryBuilder->setParameter('from', $filter->getFrom()->format('YmdHis'));
        }

        if (!\is_null($filter->getTo())) {
            $queryBuilder->andWhere('l.time < :to');
            $queryBuilder->setParameter('to', $filter->getTo()->format('YmdHis'));
        }

        if ('*' !== $appUUID->composeUUID()) {
            $queryBuilder->andWhere('l.app_uuid = :app_uuid');
            $queryBuilder->setParameter('app_uuid', $appUUID->composeUUID());
        }

        if (
            !\is_null($indexUUID) &&
            '' !== $indexUUID->composeUUID() &&
            '*' !== $indexUUID->composeUUID()
        ) {
            $queryBuilder->andWhere('l.index_uuid = :index_uuid');
            $queryBuilder->setParameter('index_uuid', $indexUUID->composeUUID());
        }

        if (!empty($filter->getTypes())) {
            $ors = [];
            foreach ($filter->getTypes() as $filter) {
                $ors[] = $queryBuilder->expr()->or($queryBuilder->expr()->eq('l.type', ':'.$filter));
                $queryBuilder->setParameter($filter, $filter);
            }

            $queryBuilder->andWhere(\implode(' OR ', $ors));
        }
    }
}
