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

namespace Apisearch\Plugin\DBAL\Domain\PurchaseRepository;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\DBAL\Domain\DBALException;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\PurchaseRepository\Purchase;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseFilter;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseItem;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseItems;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseRepository;
use Apisearch\Server\Domain\Repository\PurchaseRepository\Purchases;
use DateTime;
use Doctrine\DBAL\Exception as ExternalDBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class DBALPurchaseRepository.
 */
class DBALPurchaseRepository implements PurchaseRepository
{
    private Connection $connection;
    private string $purchasesTableName;
    private string $purchaseItemsTableName;

    /**
     * @param Connection $connection
     * @param string     $purchasesTable
     * @param string     $purchaseItemsTable
     */
    public function __construct(
        Connection $connection,
        string $purchasesTable,
        string $purchaseItemsTable
    ) {
        $this->connection = $connection;
        $this->purchasesTableName = $purchasesTable;
        $this->purchaseItemsTableName = $purchaseItemsTable;
    }

    /**
     * @param RepositoryReference $repositoryReference
     * @param string              $user
     * @param DateTime            $when
     * @param ItemUUID[]          $itemsUUID
     *
     * @return PromiseInterface
     */
    public function registerPurchase(
        RepositoryReference $repositoryReference,
        string $user,
        DateTime $when,
        array $itemsUUID
    ): PromiseInterface {
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        return $this
            ->connection
            ->insert($this->purchasesTableName, [
                'app_uuid' => $appUUID instanceof AppUUID ? $appUUID->composeUUID() : null,
                'index_uuid' => $indexUUID instanceof IndexUUID ? $indexUUID->composeUUID() : null,
                'user_uuid' => $user,
                'time' => $when->format('Ymd'),
            ])
            ->then(function (Result $result) use ($itemsUUID) {
                $purchaseId = $result->getLastInsertedId();

                return all(\array_map(function (ItemUUID $itemUUID) use ($purchaseId) {
                    return $this
                        ->connection
                        ->insert($this->purchaseItemsTableName, [
                            'purchase_id' => $purchaseId,
                            'item_uuid' => $itemUUID->composeUUID(),
                        ]);
                }, $itemsUUID));
            })
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * @param PurchaseFilter $purchaseFilter
     *
     * @return PromiseInterface<Purchases>
     */
    public function getRegisteredPurchases(PurchaseFilter $purchaseFilter): PromiseInterface
    {
        $repositoryReference = $purchaseFilter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        if (!$appUUID instanceof AppUUID) {
            return resolve([]);
        }

        $queryBuilder = $this
            ->connection
            ->createQueryBuilder()
            ->from($this->purchasesTableName, 'p');

        $perDay = $purchaseFilter->isPerDay();
        $fields = [];
        $groupBy = [];

        if ($purchaseFilter->isPerDay()) {
            $fields[] = 'p.time';
            $groupBy[] = 'p.time';
        }

        if (\is_null($purchaseFilter->getCount())) {
            $fields = ['p.*', 'group_concat(pi.item_uuid) as items_uuid'];
            $queryBuilder->leftJoin('p', $this->purchaseItemsTableName, 'pi', 'pi.purchase_id = p.id');
            $groupBy[] = 'p.id';
        } else {
            $fields[] = PurchaseFilter::UNIQUE_USERS === $purchaseFilter->getCount()
                ? 'COUNT(distinct p.user_uuid) as count'
                : 'COUNT(distinct p.id) as count';

            if ($purchaseFilter->getItemUUID()) {
                $queryBuilder->leftJoin('p', $this->purchaseItemsTableName, 'pi', 'pi.purchase_id = p.id');
            }
        }

        $queryBuilder->select(\implode(', ', $fields));
        if (!empty($groupBy)) {
            $queryBuilder->groupBy(\implode(', ', $groupBy));
        }

        $parameters = [];

        if (!\is_null($purchaseFilter->getItemUUID())) {
            $queryBuilder->andWhere('EXISTS('.$this
                ->connection
                ->createQueryBuilder()
                ->select('*')
                ->from($this->purchaseItemsTableName, 'pi2')
                ->where('pi2.purchase_id = p.id')
                ->andWhere('pi2.item_uuid = :item_uuid')
                ->setParameters([
                    'item_uuid' => $purchaseFilter->getItemUUID(),
                ])->getSQL()
            .')');
            $parameters[] = $purchaseFilter->getItemUUID()->composeUUID();
        }

        $this->applyFilterToQueryBuilder($queryBuilder, $purchaseFilter, $parameters);
        $queryBuilder->setParameters($parameters);

        return $this
            ->connection
            ->query($queryBuilder)
            ->then(function (Result $result) use ($perDay, $purchaseFilter) {
                return $perDay
                    ? $this->formatResultsPerDay($result->fetchAllRows(), !\is_null($purchaseFilter->getCount()))
                    : (
                        \is_null($purchaseFilter->getCount())
                            ? $this->arrayToPurchases($result->fetchAllRows())
                            : \intval($result->fetchFirstRow()['count'])
                    );
            })
            ->otherwise(function (ExternalDBALException $exception) {
                throw new DBALException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            });
    }

    /**
     * Apply filters to querybuilder given a filter.
     *
     * @param QueryBuilder   $queryBuilder
     * @param PurchaseFilter $filter
     * @param array          $parameters
     *
     * @return void
     */
    private function applyFilterToQueryBuilder(
        QueryBuilder $queryBuilder,
        PurchaseFilter $filter,
        array &$parameters
    ): void {
        $repositoryReference = $filter->getRepositoryReference();
        $appUUID = $repositoryReference->getAppUUID();
        $indexUUID = $repositoryReference->getIndexUUID();

        if (!\is_null($filter->getFrom())) {
            $queryBuilder->andWhere('p.time >= ?');
            $parameters[] = $filter->getFrom()->format('Ymd');
        }

        if (!\is_null($filter->getTo())) {
            $queryBuilder->andWhere('p.time < ?');
            $parameters[] = $filter->getTo()->format('Ymd');
        }

        if ('*' !== $appUUID->composeUUID()) {
            $queryBuilder->andWhere('p.app_uuid = ?');
            $parameters[] = $appUUID->composeUUID();
        }

        if (
            !\is_null($indexUUID) &&
            '' !== $indexUUID->composeUUID() &&
            '*' !== $indexUUID->composeUUID()
        ) {
            $queryBuilder->andWhere('p.index_uuid = ?');
            $parameters[] = $indexUUID->composeUUID();
        }

        if (!\is_null($filter->getUser())) {
            $queryBuilder->andWhere('p.user_uuid = ?');
            $parameters[] = $filter->getUser();
        }
    }

    /**
     * Format results per day.
     *
     * @param array $rows
     *
     * @return array
     */
    private function formatResultsPerDay(array $rows, bool $count): array
    {
        return $count
            ? $this->formatCountResultsPerDay($rows)
            : $this->formatFullResultsPerDay($rows);
    }

    /**
     * @param array $rows
     *
     * @return array
     */
    private function formatCountResultsPerDay(array $rows): array
    {
        $indexedRows = [];
        foreach ($rows as $row) {
            $indexedRows[$row['time']] = \intval($row['count']);
        }

        return $indexedRows;
    }

    /**
     * @param array $rows
     *
     * @return array
     */
    private function formatFullResultsPerDay(array $rows): array
    {
        $indexedRows = [];
        foreach ($rows as $row) {
            \array_key_exists($row['time'], $indexedRows)
                ? $indexedRows[$row['time']]++
                : ($indexedRows[$row['time']] = 1);
        }

        return $indexedRows;
    }

    /**
     * @param array[] $purchasesAsArray
     *
     * @return Purchase[]
     */
    private function arrayToPurchases(array $purchasesAsArray): array
    {
        return \array_map(
            fn (array $purchaseAsArray) => $this->arrayToPurchase($purchaseAsArray),
            $purchasesAsArray
        );
    }

    /**
     * @param array $purchaseAsArray
     *
     * @return Purchase
     */
    private function arrayToPurchase(array $purchaseAsArray): Purchase
    {
        return new Purchase(
            $purchaseAsArray['app_uuid'],
            $purchaseAsArray['index_uuid'],
            $purchaseAsArray['user_uuid'],
            DateTime::createFromFormat('Ymd', \strval($purchaseAsArray['time'])),
            new PurchaseItems(\array_map(function (string $itemUUIDComposed) {
                return new PurchaseItem(
                    ItemUUID::createByComposedUUID($itemUUIDComposed)
                );
            }, \explode(',', $purchaseAsArray['items_uuid'] ?? '')))
        );
    }
}
