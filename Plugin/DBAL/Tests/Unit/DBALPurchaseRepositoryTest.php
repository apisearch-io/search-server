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

use Apisearch\Plugin\DBAL\Domain\PurchaseRepository\DBALPurchaseRepository;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\PurchaseRepository\PurchaseRepositoryTest;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Connection;
use React\EventLoop\LoopInterface;

class DBALPurchaseRepositoryTest extends PurchaseRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return PurchaseRepository
     */
    public function getEmptyRepository(LoopInterface $loop): PurchaseRepository
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
     * @return DBALPurchaseRepository
     */
    public static function createEmptyRepository(Connection $connection): DBALPurchaseRepository
    {
        $purchaseTableName = 'purchase';
        $schema = new Schema();
        $table = $schema->createTable($purchaseTableName);
        $table->addColumn('id', 'integer', ['length' => 11, 'autoincrement' => true]);
        $table->addColumn('app_uuid', 'string', ['length' => 50]);
        $table->addColumn('index_uuid', 'string', ['length' => 50]);
        $table->addColumn('user_uuid', 'string', ['length' => 25]);
        $table->addColumn('time', 'integer', ['length' => 8]);
        $table->setPrimaryKey(['id']);

        $connection->executeSchema($schema);

        $purchaseItemTableName = 'purchase_item';
        $schema = new Schema();
        $table = $schema->createTable($purchaseItemTableName);
        $table->addColumn('purchase_id', 'string', ['length' => 50]);
        $table->addColumn('item_uuid', 'string', ['length' => 50]);

        $connection->executeSchema($schema);

        return new DBALPurchaseRepository($connection, $purchaseTableName, $purchaseItemTableName);
    }
}
