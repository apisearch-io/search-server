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

use Apisearch\Plugin\DBAL\Domain\InteractionRepository\DBALInteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\InteractionRepository\InteractionRepositoryTest;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use React\EventLoop\LoopInterface;

/**
 * Class DBALInteractionRepositoryTest.
 */
class DBALInteractionRepositoryTest extends InteractionRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return InteractionRepository
     */
    public function getEmptyRepository(LoopInterface $loop): InteractionRepository
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
     * @return InteractionRepository
     */
    public static function createEmptyRepository(Connection $connection): InteractionRepository
    {
        $tableName = 'interactions';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('user_uuid', 'string', ['length' => 25]);
        $table->addColumn('app_uuid', 'string', ['length' => 50]);
        $table->addColumn('index_uuid', 'string', ['length' => 50]);
        $table->addColumn('item_uuid', 'string', ['length' => 50]);
        $table->addColumn('ip', 'string', ['length' => 16]);
        $table->addColumn('host', 'string', ['length' => 50]);
        $table->addColumn('platform', 'string', ['length' => 25]);
        $table->addColumn('type', 'string', ['length' => 3]);
        $table->addColumn('time', 'integer', ['length' => 8]);

        $table->addIndex(['time', 'app_uuid']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'item_uuid']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'ip']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'host']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'platform']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'user_uuid']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'type']);

        $connection->executeSchema($schema);

        return new DBALInteractionRepository($connection, $tableName);
    }
}
