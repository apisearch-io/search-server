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

use Apisearch\Plugin\DBAL\Domain\AppRepository\DBALConfigRepository;
use Apisearch\Plugin\DBAL\Domain\Encrypter\EmptyEncrypter;
use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\AppRepository\ConfigRepositoryTest;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use React\EventLoop\LoopInterface;

/**
 * Class DBALConfigRepositoryTest.
 */
class DBALConfigRepositoryTest extends ConfigRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return ConfigRepository
     */
    public function buildEmptyRepository(LoopInterface $loop): ConfigRepository
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
     * @param Connection $connection
     *
     * @return DBALConfigRepository
     */
    public static function createEmptyRepository(Connection $connection): ConfigRepository
    {
        $tableName = 'config';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('repository_reference_uuid', 'string', ['length' => 255]);
        $table->addColumn('content', 'text');

        $connection->executeSchema($schema);

        return new DBALConfigRepository($connection, new EmptyEncrypter(), $tableName);
    }
}
