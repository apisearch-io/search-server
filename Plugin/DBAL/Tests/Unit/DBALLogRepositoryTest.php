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

use Apisearch\Plugin\DBAL\Domain\LogRepository\DBALLogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\LogRepository\LogRepositoryTest;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use React\EventLoop\LoopInterface;

/**
 * Class DBALLogRepositoryTest.
 */
class DBALLogRepositoryTest extends LogRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    public function getEmptyRepository(LoopInterface $loop): LogRepository
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
     * @return DBALLogRepository
     */
    public static function createEmptyRepository(Connection $connection): DBALLogRepository
    {
        $tableName = 'logs';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('app_uuid', 'string', ['length' => 50]);
        $table->addColumn('index_uuid', 'string', ['length' => 50]);
        $table->addColumn('time', 'integer', ['length' => 8]);
        $table->addColumn('n', 'integer', ['length' => 6]);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->addColumn('params', 'string', ['length' => 255]);

        $table->addIndex(['app_uuid']);
        $table->addIndex(['index_uuid']);
        $table->addIndex(['time']);
        $table->addIndex(['type']);
        $table->addIndex(['params']);
        $connection->executeSchema($schema);

        return new DBALLogRepository($connection, $tableName);
    }
}
