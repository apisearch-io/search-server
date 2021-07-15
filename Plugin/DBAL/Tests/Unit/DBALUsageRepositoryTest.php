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

namespace Apisearch\Plugin\DBAL\Tests\Unit;

use Apisearch\Plugin\DBAL\Domain\UsageRepository\DBALUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\UsageRepository\UsageRepositoryTest;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class DBALUsageRepositoryTest.
 */
class DBALUsageRepositoryTest extends UsageRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    public function getEmptyRepository(LoopInterface $loop): UsageRepository
    {
        return static::createEmptyRepository(
            DBALConnectionFactory::create($loop)
        );
    }

    /**
     * Create new EmptyRepository.
     *
     * @param Connection $connection
     *
     * @return DBALUsageRepository
     */
    public static function createEmptyRepository(Connection $connection): DBALUsageRepository
    {
        $tableName = 'uses';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('event', 'string', ['length' => 15]);
        $table->addColumn('app_uuid', 'string', ['length' => 50]);
        $table->addColumn('index_uuid', 'string', ['length' => 50]);
        $table->addColumn('time', 'integer', ['length' => 8]);
        $table->addColumn('n', 'integer', ['length' => 6]);

        $table->addIndex(['event']);
        $table->addIndex(['app_uuid']);
        $table->addIndex(['index_uuid']);
        $table->addIndex(['time']);
        $connection->executeSchema($schema);

        return new DBALUsageRepository($connection, $tableName);
    }

    /**
     * Get number of rows.
     *
     * @param UsageRepository $repository
     *
     * @return PromiseInterface<int>
     */
    public function getNumberOfRows(UsageRepository $repository): PromiseInterface
    {
        /**
         * @var DBALUsageRepository
         */
        $connection = $repository->getConnection();
        $tableName = $repository->getTableName();

        return $connection
            ->query($connection
                ->createQueryBuilder()
                ->select('count(*) as count')
                ->from($tableName, 'u')
            )
            ->then(function (Result $result) {
                return \intval($result->fetchFirstRow()['count']);
            });
    }
}
