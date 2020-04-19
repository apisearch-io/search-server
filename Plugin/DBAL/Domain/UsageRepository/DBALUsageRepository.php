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

namespace Apisearch\Plugin\DBAL\Domain\UsageRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use DateTime;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class DBALUsageRepositoryTest.
 */
class DBALUsageRepository implements UsageRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @param Connection $connection
     * @param string     $usageLinesTable
     */
    public function __construct(Connection $connection, string $usageLinesTable)
    {
        $this->connection = $connection;
        $this->tableName = $usageLinesTable;
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvent(
        RepositoryReference $repositoryReference,
        string $eventName,
        DateTime $when,
        int $n = 1
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        return $this
            ->connection
            ->insert($this->tableName, [
                'event' => $eventName,
                'app_uuid' => $appUUID instanceof AppUUID ? $appUUID->composeUUID() : null,
                'index_uuid' => $indexUUID instanceof IndexUUID ? $indexUUID->composeUUID() : null,
                'time' => $when->getTimestamp(),
                'n' => $n,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegisteredEvents(
        RepositoryReference $repositoryReference,
        ?string $eventType,
        DateTime $from,
        ?DateTime $to = null
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $queryBuilder = $this
            ->connection
            ->createQueryBuilder()
            ->select('u.event as e, SUM(u.n) as s')
            ->from($this->tableName, 'u')
            ->where('u.time >= ?')
            ->andWhere('u.app_uuid = ?')
            ->groupBy('u.event');

        $parameters = [
            $from->getTimestamp(),
            $appUUID->composeUUID(),
        ];

        if (!\is_null($eventType)) {
            $queryBuilder->andWhere('u.event = ?');
            $parameters[] = $eventType;
        }

        if (
            !\is_null($indexUUID) &&
            '' !== $indexUUID->composeUUID() &&
            '*' !== $indexUUID->composeUUID()
        ) {
            $queryBuilder->andWhere('u.index_uuid = ?');
            $parameters[] = $indexUUID->composeUUID();
        }

        if (!\is_null($to)) {
            $queryBuilder->andWhere('u.time < ?');
            $parameters[] = $to->getTimestamp();
        }

        $queryBuilder->setParameters($parameters);

        return $this
            ->connection
            ->query($queryBuilder)
            ->then(function (Result $result) {
                $indexedRows = [];
                foreach ($result->fetchAllRows() as $row) {
                    $indexedRows[$row['e']] = $row['s'];
                }

                return $indexedRows;
            });
    }
}
