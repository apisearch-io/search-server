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

use Apisearch\Plugin\DBAL\Domain\AppRepository\DBALTokenRepository;
use Apisearch\Plugin\DBAL\Domain\Encrypter\EmptyEncrypter;
use Apisearch\Server\Domain\Repository\AppRepository\TokenRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\AppRepository\TokenRepositoryTest;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use React\EventLoop\LoopInterface;

/**
 * Class DBALTokenRepositoryTest.
 */
class DBALTokenRepositoryTest extends TokenRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return TokenRepository
     */
    public function buildEmptyRepository(LoopInterface $loop): TokenRepository
    {
        return static::createEmptyRepository(
            DBALConnectionFactory::create($loop)
        );
    }

    /**
     * @param Connection $connection
     *
     * @return DBALTokenRepository
     */
    public static function createEmptyRepository(Connection $connection): TokenRepository
    {
        $tableName = 'tokens';
        $schema = new Schema();
        $table = $schema->createTable($tableName);
        $table->addColumn('token_uuid', 'string', ['length' => 255]);
        $table->addColumn('app_uuid', 'string', ['length' => 50]);
        $table->addColumn('content', 'text');

        $connection->executeSchema($schema);

        return new DBALTokenRepository($connection, new EmptyEncrypter(), $tableName, false);
    }
}
