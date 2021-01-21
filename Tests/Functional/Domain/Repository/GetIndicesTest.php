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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

use Apisearch\Config\Config;
use Apisearch\Model\Index;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Model\User;
use Apisearch\Query\Query;

/**
 * Class IndexStatusTest.
 */
trait GetIndicesTest
{
    /**
     * Test index check.
     */
    public function testGetIndicesWithAppid(): void
    {
        $indices = $this->getIndices(self::$appId);
        $this->assertCount(2, $indices);
        $index = \array_shift($indices);
        $this->assertInstanceOf(Index::class, $index);
    }

    /**
     * Test index check.
     */
    public function testGetIndices(): void
    {
        $indices = \array_values($this->getIndices('*'));
        $this->assertTrue(\count($indices) >= 2);
    }

    /**
     * Get shards and replicas values with allocation.
     *
     * @return void
     */
    public function testShardsReplicasInGetIndicesAllocated(): void
    {
        $appId = '26178621test-shards-and-replicas-allocated';
        $indexId = 'index-shards-and-replicas-allocated';
        static::safeDeleteIndex($appId, $indexId);

        $this->createIndex(
            $appId,
            $indexId,
            null,
            Config::createFromArray([
                'shards' => 1,
                'replicas' => 0,
            ])
        );

        $indices = $this->getIndices($appId);
        $firstIndex = \reset($indices);
        $this->assertEquals(1, $firstIndex->getShards());
        $this->assertEquals(0, $firstIndex->getReplicas());
        $this->assertTrue($firstIndex->getMetadata()['allocated']);
        static::deleteIndex($appId, $indexId);
    }

    /**
     * Get shards and replicas values.
     *
     * @return void
     */
    public function testShardsReplicasInGetIndicesNotAllocated(): void
    {
        $appId = '26178621test-shards-and-replicas-not-allocated';
        $indexId = 'index-shards-and-replicas-not-allocated';
        static::safeDeleteIndex($appId, $indexId);

        $this->createIndex(
            $appId,
            $indexId,
            null,
            Config::createFromArray([
                'shards' => 5,
                'replicas' => 4,
            ])
        );

        $indices = $this->getIndices($appId);
        $firstIndex = \reset($indices);
        $this->assertEquals(5, $firstIndex->getShards());
        $this->assertEquals(4, $firstIndex->getReplicas());
        $this->assertFalse($firstIndex->getMetadata()['allocated']);
        static::deleteIndex($appId, $indexId);
    }

    /**
     * Test get indices with deleted index.
     *
     * @group lol
     *
     * @return void
     */
    public function testGetIndicesWithDeletedIndex(): void
    {
        $newIndexUUID = 'mynewindex';
        static::safeDeleteIndex(self::$appId, $newIndexUUID);
        $indices = \count($this->getIndices(self::$appId));
        static::createIndex(self::$appId, $newIndexUUID, $this->getGodToken(self::$appId), Config::createEmpty());
        $indicesPlusOne = \count($this->getIndices(self::$appId));
        static::indexItems([Item::create(ItemUUID::createByComposedUUID('1~lol'), ['field1' => 'value1'])], self::$appId, $newIndexUUID);
        $result = $this->query(Query::createMatchAll()->byUser(User::createFromArray(['id' => '123'])), self::$appId, $newIndexUUID);
        $this->assertCount(1, $result->getItems());
        $this->assertEquals($indices + 1, $indicesPlusOne);

        $this->deleteIndex(self::$appId, $newIndexUUID);
        $indicesAsItWasBefore = \count($this->getIndices(self::$appId));
        $this->assertEquals($indices, $indicesAsItWasBefore);
    }
}
