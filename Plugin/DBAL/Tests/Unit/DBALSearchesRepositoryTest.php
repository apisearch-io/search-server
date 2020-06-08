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

use Apisearch\Plugin\DBAL\Domain\SearchesRepository\DBALSearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\SearchesRepository\SearchesRepositoryTest;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use React\EventLoop\LoopInterface;

/**
 * Class DBALSearchesRepositoryTest.
 */
class DBALSearchesRepositoryTest extends SearchesRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return SearchesRepository
     */
    public function getEmptyRepository(LoopInterface $loop): SearchesRepository
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
     * @return DBALSearchesRepository
     */
    public static function createEmptyRepository(Connection $connection): DBALSearchesRepository
    {
        $tableName = 'searches';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('user_uuid', 'string', ['length' => 25]);
        $table->addColumn('app_uuid', 'string', ['length' => 50]);
        $table->addColumn('index_uuid', 'string', ['length' => 50]);
        $table->addColumn('text', 'string', ['length' => 50]);
        $table->addColumn('nb_of_results', 'integer', ['length' => 8]);
        $table->addColumn('with_results', 'boolean');
        $table->addColumn('ip', 'string', ['length' => 16]);
        $table->addColumn('host', 'string', ['length' => 50]);
        $table->addColumn('platform', 'string', ['length' => 25]);
        $table->addColumn('time', 'integer', ['length' => 8]);

        $table->addIndex(['time', 'app_uuid']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'ip']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'host']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'platform']);
        $table->addIndex(['time', 'app_uuid', 'index_uuid', 'user_uuid']);

        $connection->executeSchema($schema);

        return new DBALSearchesRepository($connection, $tableName);
    }
}
