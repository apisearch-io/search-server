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
use Apisearch\Plugin\DBAL\Domain\MetadataRepository\DBALMetadataRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\MetadataRepository\MetadataRepositoryTest;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use React\EventLoop\LoopInterface;

/**
 * Class DBALMetadataRepositoryTest.
 */
class DBALMetadataRepositoryTest extends MetadataRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return InteractionRepository
     */
    public function buildEmptyRepository(LoopInterface $loop): MetadataRepository
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
     * @return DBALInteractionRepository
     */
    public static function createEmptyRepository(Connection $connection): DBALMetadataRepository
    {
        $tableName = 'metadata';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('repository_reference_uuid', 'string', ['length' => 255]);
        $table->addColumn('`key`', 'string', ['length' => 15]);
        $table->addColumn('val', 'text');
        $table->addColumn('factory', 'string', ['length' => 128, 'default' => null, 'notnull' => false]);

        $connection->executeSchema($schema);

        return new DBALMetadataRepository($connection, $tableName);
    }
}
