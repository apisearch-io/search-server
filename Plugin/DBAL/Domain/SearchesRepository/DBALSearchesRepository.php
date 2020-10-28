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

namespace Apisearch\Plugin\DBAL\Domain\SearchesRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class DBALSearchesRepository.
 */
final class DBALSearchesRepository implements SearchesRepository
{
    private Connection $connection;
    private string $tableName;

    /**
     * @param Connection $dbalPluginConnection
     * @param string     $searchesTable
     */
    public function __construct(
        Connection $dbalPluginConnection,
        string $searchesTable
    ) {
        $this->connection = $dbalPluginConnection;
        $this->tableName = $searchesTable;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param string              $searchText
     * @param int                 $numberOfResults
     * @param Origin              $origin
     * @param DateTime            $when
     *
     * @return PromiseInterface
     */
    public function registerSearch(
        RepositoryReference $repositoryReference,
        string $userUUID,
        string $searchText,
        int $numberOfResults,
        Origin $origin,
        DateTime $when
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();
        $when->setTime(0, 0, 0);

        return $this
            ->connection
            ->insert($this->tableName, [
                'user_uuid' => $userUUID,
                'app_uuid' => $appUUID instanceof AppUUID ? $appUUID->composeUUID() : null,
                'index_uuid' => $indexUUID instanceof IndexUUID ? $indexUUID->composeUUID() : null,
                'text' => $searchText,
                'nb_of_results' => $numberOfResults,
                'with_results' => $numberOfResults > 0,
                'ip' => $origin->getIp(),
                'host' => $origin->getHost(),
                'platform' => $origin->getPlatform(),
                'time' => $when->format('Ymd'),
            ]);
    }

    /**
     * @param SearchesFilter $filter
     *
     * @return PromiseInterface
     */
    public function getRegisteredSearches(SearchesFilter $filter): PromiseInterface
    {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $queryBuilder = $this
            ->connection
            ->createQueryBuilder()
            ->from($this->tableName, 's');

        $perDay = $filter->isPerDay();
        $fields = ['COUNT(*) as count'];
        $groupBy = [];

        if (SearchesFilter::UNIQUE_USERS === $filter->getCount()) {
            $fields = ['COUNT(distinct s.user_uuid) as count'];
        }

        if ($filter->isPerDay()) {
            $fields[] = 's.time';
            $groupBy[] = 's.time';
        }

        $queryBuilder
            ->select(\implode(', ', $fields))
            ->groupBy(\implode(', ', $groupBy));

        $parameters = [];
        $this->applyFilterToQueryBuilder($queryBuilder, $filter, $parameters);
        $queryBuilder->setParameters($parameters);

        return $this
            ->connection
            ->query($queryBuilder)
            ->then(function (Result $result) use ($perDay) {
                return $perDay
                    ? $this->formatResultsPerDay($result->fetchAllRows())
                    : \intval($result->fetchFirstRow()['count']);
            });
    }

    /**
     * @param SearchesFilter $filter
     * @param int            $n
     *
     * @return PromiseInterface
     */
    public function getTopSearches(SearchesFilter $filter, int $n): PromiseInterface
    {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $queryBuilder = $this
            ->connection
            ->createQueryBuilder()
            ->select('COUNT(*) as count, s.text as text')
            ->groupBy('s.text')
            ->orderBy('count', 'DESC')
            ->from($this->tableName, 's')
            ->setMaxResults($n);

        $parameters = [];
        $this->applyFilterToQueryBuilder($queryBuilder, $filter, $parameters);
        $queryBuilder->setParameters($parameters);

        return $this
            ->connection
            ->query($queryBuilder)
            ->then(function (Result $result) {
                $formattedRows = [];
                foreach ($result->fetchAllRows() as $row) {
                    $formattedRows[$row['text']] = \intval($row['count']);
                }

                return $formattedRows;
            });
    }

    /**
     * Apply filters to querybuilder given a filter.
     *
     * @param QueryBuilder   $queryBuilder
     * @param SearchesFilter $filter
     * @param array          $parameters
     */
    private function applyFilterToQueryBuilder(
        QueryBuilder $queryBuilder,
        SearchesFilter $filter,
        array &$parameters
    ) {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (!\is_null($filter->getFrom())) {
            $queryBuilder->andWhere('s.time >= ?');
            $parameters[] = $filter->getFrom()->format('Ymd');
        }

        if (!\is_null($filter->getTo())) {
            $queryBuilder->andWhere('s.time < ?');
            $parameters[] = $filter->getTo()->format('Ymd');
        }

        if ('*' !== $appUUID->composeUUID()) {
            $queryBuilder->andWhere('s.app_uuid = ?');
            $parameters[] = $appUUID->composeUUID();
        }

        if (
            !\is_null($indexUUID) &&
            '' !== $indexUUID->composeUUID() &&
            '*' !== $indexUUID->composeUUID()
        ) {
            $queryBuilder->andWhere('s.index_uuid = ?');
            $parameters[] = $indexUUID->composeUUID();
        }

        if (!\is_null($filter->getPlatform())) {
            if (Origin::MOBILE === $filter->getPlatform()) {
                $queryBuilder->andWhere('s.platform = ? OR s.platform = ?');
                $parameters[] = Origin::TABLET;
                $parameters[] = Origin::PHONE;
            } else {
                $queryBuilder->andWhere('s.platform = ?');
                $parameters[] = $filter->getPlatform();
            }
        }

        if (!\is_null($filter->getUser())) {
            $queryBuilder->andWhere('s.user_uuid = ?');
            $parameters[] = $filter->getUser();
        }

        if ($filter->withResultsAreExcluded()) {
            $queryBuilder->andWhere('s.with_results = ?');
            $parameters[] = false;
        }

        if ($filter->withoutResultsAreExcluded()) {
            $queryBuilder->andWhere('s.with_results = ?');
            $parameters[] = true;
        }
    }

    /**
     * Format results per day.
     *
     * @param array $rows
     *
     * @return array
     */
    private function formatResultsPerDay(array $rows): array
    {
        $indexedRows = [];
        foreach ($rows as $row) {
            $indexedRows[$row['time']] = \intval($row['count']);
        }

        return $indexedRows;
    }
}
