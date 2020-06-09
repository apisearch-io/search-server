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
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;

/**
 * Class DBALInteractionRepository.
 */
class DBALInteractionRepository implements InteractionRepository
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
     * @param string     $interactionsTable
     */
    public function __construct(
        Connection $connection,
        string $interactionsTable
    ) {
        $this->connection = $connection;
        $this->tableName = $interactionsTable;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $userUUID
     * @param ItemUUID            $itemUUID
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
                'ip' => $origin->getIp(),
                'host' => $origin->getHost(),
                'platform' => $origin->getPlatform(),
                'type' => $type,
                'time' => $when->format('Ymd'),
            ]);
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
        if ($filter->isPerDay()) {
            $queryBuilder
                ->select('COUNT(*) as count, i.time')
                ->groupBy('i.time');
        } else {
            $queryBuilder->select('COUNT(*) as count');
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
            });
    }

    /**
     * Apply filters to querybuilder given a filter.
     *
     * @param QueryBuilder      $queryBuilder
     * @param InteractionFilter $filter
     * @param array             $parameters
     */
    private function applyFilterToQueryBuilder(
        QueryBuilder $queryBuilder,
        InteractionFilter $filter,
        array &$parameters
    ) {
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