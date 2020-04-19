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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Plugin\DBAL\Domain\UsageRepository\DBALUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\UsageRepository\UsageRepositoryTest;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use React\EventLoop\LoopInterface;

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
            static::createConnection($loop)
        );
    }

    /**
     * Create connection.
     *
     * @param LoopInterface $loop
     *
     * @return Connection
     */
    public static function createConnection(LoopInterface $loop): Connection
    {
        return Connection::createConnected(
            new SQLiteDriver($loop),
            new Credentials(
                '',
                '',
                'root',
                'root',
                ':memory:'
            ),
            new SqlitePlatform()
        );
    }

    /**
     * Create new EmptyRepository.
     *
     * @param Connection $connection
     *
     * @return UsageRepository
     */
    public static function createEmptyRepository(Connection $connection): UsageRepository
    {
        $tableName = 'uses';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('event', 'string', ['length' => 15]);
        $table->addColumn('app_uuid', 'string', ['length' => 50]);
        $table->addColumn('index_uuid', 'string', ['length' => 50]);
        $table->addColumn('time', 'integer', ['length' => 11]);
        $table->addColumn('n', 'integer', ['length' => 6]);

        $table->addIndex(['event']);
        $table->addIndex(['app_uuid']);
        $table->addIndex(['index_uuid']);
        $table->addIndex(['time']);
        $connection->executeSchema($schema);

        return new DBALUsageRepository($connection, $tableName);
    }
}
