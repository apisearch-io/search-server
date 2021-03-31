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

namespace Apisearch\Plugin\DBAL\Domain\InteractionRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\DBAL\Domain\DBALException;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use DateTime;
use Doctrine\DBAL\Exception as ExternalDBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class DBALInteractionRepository.
 */
final class DBALInteractionRepository implements InteractionRepository
{
    private Connection $connection;
    private string $tableName;

    /**
     * @param Connection $dbalPluginConnection
     * @param string     $interactionsTable
     */
    public function __construct(
        Connection $dbalPluginConnection,
        string $interactionsTable
    ) {
        $this->connection = $dbalPluginConnection;
        $this->tableName = $interactionsTable;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param ItemUUID            $itemUUID
     * @param int                 $position
     * @param string|null         $context
     * @param Origin              $origin
     * @param string              $type
     * @param DateTime            $when
     *
     * @return PromiseInterface
     */
    public function registerInteraction(
        RepositoryReference $repositoryReference,
        string $userUUID,
        ItemUUID $itemUUID,
        int $position,
        ?string $context,
        Origin $origin,
        string $type,
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
                'item_uuid' => $itemUUID->composeUUID(),
                'position' => $position,
                'context' => $context,
                'ip' => $origin->getIp(),
                'host' => $origin->getHost(),
                'platform' => $origin->getPlatform(),
                'type' => $type,
                'time' => $when->format('Ymd'),
            ])
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * @param InteractionFilter $filter
     *
     * @return PromiseInterface
     */
    public function getRegisteredInteractions(InteractionFilter $filter): PromiseInterface
    {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $queryBuilder = $this
            ->connection
            ->createQueryBuilder()
            ->from($this->tableName, 'i');

        $perDay = $filter->isPerDay();
        $fields = ['COUNT(*) as count'];
        $groupBy = [];

        if (InteractionFilter::UNIQUE_USERS === $filter->getCount()) {
            $fields = ['COUNT(distinct i.user_uuid) as count'];
        }

        if ($filter->isPerDay()) {
            $fields[] = 'i.time';
            $groupBy[] = 'i.time';
        }

        $queryBuilder->select(\implode(', ', $fields));
        if (!empty($groupBy)) {
            $queryBuilder->groupBy(\implode(', ', $groupBy));
        }

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
            })
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * @param InteractionFilter $filter
     * @param int               $n
     *
     * @return PromiseInterface
     */
    public function getTopInteractedItems(
        InteractionFilter $filter,
        int $n
    ): PromiseInterface {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $queryBuilder = $this
            ->connection
            ->createQueryBuilder()
            ->select('COUNT(*) as count, i.item_uuid')
            ->groupBy('i.item_uuid')
            ->orderBy('count', 'DESC')
            ->from($this->tableName, 'i')
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
                    $formattedRows[$row['item_uuid']] = \intval($row['count']);
                }

                return $formattedRows;
            })
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * Apply filters to querybuilder given a filter.
     *
     * @param QueryBuilder      $queryBuilder
     * @param InteractionFilter $filter
     * @param array             $parameters
     *
     * @return void
     */
    private function applyFilterToQueryBuilder(
        QueryBuilder $queryBuilder,
        InteractionFilter $filter,
        array &$parameters
    ): void {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (!\is_null($filter->getFrom())) {
            $queryBuilder->andWhere('i.time >= ?');
            $parameters[] = $filter->getFrom()->format('Ymd');
        }

        if (!\is_null($filter->getTo())) {
            $queryBuilder->andWhere('i.time < ?');
            $parameters[] = $filter->getTo()->format('Ymd');
        }

        if ('*' !== $appUUID->composeUUID()) {
            $queryBuilder->andWhere('i.app_uuid = ?');
            $parameters[] = $appUUID->composeUUID();
        }

        if (
            !\is_null($indexUUID) &&
            '' !== $indexUUID->composeUUID() &&
            '*' !== $indexUUID->composeUUID()
        ) {
            $queryBuilder->andWhere('i.index_uuid = ?');
            $parameters[] = $indexUUID->composeUUID();
        }

        if (!\is_null($filter->getPlatform())) {
            if (Origin::MOBILE === $filter->getPlatform()) {
                $queryBuilder->andWhere('i.platform = ? OR i.platform = ?');
                $parameters[] = Origin::TABLET;
                $parameters[] = Origin::PHONE;
            } else {
                $queryBuilder->andWhere('i.platform = ?');
                $parameters[] = $filter->getPlatform();
            }
        }

        if (!\is_null($filter->getType())) {
            $queryBuilder->andWhere('i.type = ?');
            $parameters[] = $filter->getType();
        }

        if (!\is_null($filter->getContext())) {
            $queryBuilder->andWhere('i.context = ?');
            $parameters[] = $filter->getContext();
        }

        if (!\is_null($filter->getUser())) {
            $queryBuilder->andWhere('i.user_uuid = ?');
            $parameters[] = $filter->getUser();
        }

        if (!\is_null($filter->getItemUUID())) {
            $queryBuilder->andWhere('i.item_uuid = ?');
            $parameters[] = $filter->getItemUUID()->composeUUID();
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
