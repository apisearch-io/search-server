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

namespace Apisearch\Server\Tests\Unit\Domain\Repository;

use Apisearch\Config\Config;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Changes;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\FullRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

/**
 * Class FullRepositoryTest.
 */
abstract class FullRepositoryTest extends BaseUnitTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return FullRepository
     */
    abstract protected function getFullRepository(LoopInterface $loop = null): FullRepository;

    /**
     * Test delete index on empty repository.
     */
    public function testDeleteIndexOnEmpty()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createAppRepositoryReference();
        $this->expectException(ResourceNotAvailableException::class);
        $this->await($repository->deleteIndex($repositoryReference, $this->createIndexUUID()));
    }

    /**
     * Test delete index on wrong repository.
     */
    public function testDeleteIndexOnWrongIndex()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createAppRepositoryReference('app2');
        $repository->createIndex($repositoryReference, $this->createIndexUUID('app2'), $this->createConfig());
        $this->expectException(ResourceNotAvailableException::class);
        $this->await($repository->deleteIndex($repositoryReference, $this->createIndexUUID()));
    }

    /**
     * Test index creation.
     */
    public function testIndexCreation()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createAppRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->assertCount(1, $this->await($repository->getIndices($repositoryReference)));
    }

    /**
     * Test index deletion.
     */
    public function testIndexDeletion()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createAppRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->deleteIndex($repositoryReference, $this->createIndexUUID()));
        $this->assertEmpty($this->await($repository->getIndices($repositoryReference)));
    }

    /**
     * Test index configure on empty.
     */
    public function testIndexConfigureOnEmpty()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createAppRepositoryReference();
        $this->expectException(ResourceNotAvailableException::class);
        $this->await($repository->configureIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
    }

    /**
     * Test index configure on empty.
     */
    public function testIndexConfigure()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createAppRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->configureIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->assertCount(1, $this->await($repository->getIndices($repositoryReference)));
        $this->await($repository->deleteIndex($repositoryReference, $this->createIndexUUID()));
        $this->assertEmpty($this->await($repository->getIndices($repositoryReference)));
    }

    /**
     * Test get items on non existing index.
     */
    public function testGetItemsOnNonExistingIndex()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->expectException(ResourceNotAvailableException::class);
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
    }

    /**
     * Test get items on created index.
     */
    public function testGetItemsOnCreatedIndex()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $result = $this->await($repository->query($repositoryReference, $this->createQuery()));
        $this->assertCount(2, $result->getItems());

        $indices = $this->await($repository->getIndices($repositoryReference));
        $firstIndex = \reset($indices);
        $this->assertEquals(2, $firstIndex->getDocCount());
    }

    /**
     * Test delete items on non existing index.
     */
    public function testDeleteItemsOnNonExistingIndex()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->expectException(ResourceNotAvailableException::class);
        $this->await($repository->deleteItems($repositoryReference, $this->createItems()));
    }

    /**
     * Test update items on non existing index.
     */
    public function testUpdateItemsOnNonExistingIndex()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->expectException(ResourceNotAvailableException::class);
        $this->await($repository->updateItems($repositoryReference, $this->createQuery(), new Changes()));
    }

    /**
     * Test delete Items.
     */
    public function testDeleteItems()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $this->await($repository->deleteItems($repositoryReference, [$this->createItemUUID()]));
        $result = $this->await($repository->query($repositoryReference, $this->createQuery()));
        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getTotalHits());
        $this->assertEquals(1, $result->getTotalItems());
    }

    /**
     * Test Multiquery.
     */
    public function testMultiQuery()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $result = $this->await($repository->query($repositoryReference, $this->createMultiQuery()));
        $subResults = $result->getSubresults();
        $this->assertCount(2, $subResults);
        $this->assertCount(2, $subResults[0]->getItems());
        $this->assertEquals(2, $subResults[1]->getTotalHits());
        $this->assertEquals(2, $subResults[1]->getTotalItems());
    }

    /**
     * Test query filter by ids.
     */
    public function testQueryFilterByIds()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $result = $this->await($repository->query($repositoryReference, Query::createByUUID(new ItemUUID('item1', 'type'))));
        $this->assertEquals(1, $result->getTotalHits());
        $this->assertEquals('item1', $result->getFirstItem()->getId());
    }

    /**
     * Test query fields.
     */
    public function testFields()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $result = $this->await($repository->query($repositoryReference, $this->createQuery()));
        $firstResult = $result->getFirstItem();
        $this->assertTrue(\array_key_exists('field', $firstResult->getMetadata()));
        $this->assertTrue(\array_key_exists('another_field', $firstResult->getMetadata()));

        $result = $this->await($repository->query($repositoryReference, $this->createQuery()->setFields(['metadata.field'])));
        $firstResult = $result->getFirstItem();
        $this->assertTrue(\array_key_exists('field', $firstResult->getMetadata()));
        $this->assertFalse(\array_key_exists('another_field', $firstResult->getMetadata()));

        $result = $this->await($repository->query($repositoryReference, $this->createQuery()->setFields(['!metadata.field'])));
        $firstResult = $result->getFirstItem();
        $this->assertFalse(\array_key_exists('field', $firstResult->getMetadata()));
        $this->assertTrue(\array_key_exists('another_field', $firstResult->getMetadata()));

        $result = $this->await($repository->query($repositoryReference, $this->createQuery()->setFields(['metadata.field', '!metadata.field'])));
        $firstResult = $result->getFirstItem();
        $this->assertFalse(\array_key_exists('field', $firstResult->getMetadata()));
        $this->assertFalse(\array_key_exists('another_field', $firstResult->getMetadata()));
    }

    /**
     * Test size.
     */
    public function testSize()
    {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $this->await($repository->addItems($repositoryReference, $this->createItems('item3', 'item4')));
        $this->await($repository->addItems($repositoryReference, $this->createItems('item5', 'item6')));
        $result = $this->await($repository->query($repositoryReference, Query::create('', 0, 3)));
        $this->assertCount(3, $result->getItems());
        $this->assertEquals(6, $result->getTotalHits());
        $this->assertEquals(6, $result->getTotalItems());
        $this->assertEquals('item1', $result->getFirstItem()->getId());
    }

    /**
     * Test export.
     */
    public function testIndexExport()
    {
        $loop = Factory::create();
        $repository = $this->getFullRepository($loop);
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $stream = $this->await($repository->exportIndex($repositoryReference));
        $deferred = new Deferred();
        $items = [];
        $stream->on('data', function (Item $item) use (&$items) {
            $items[] = $item;
        });
        $stream->on('end', function () use (&$items, $deferred) {
            $deferred->resolve($items);
        });

        $data = $this->await($deferred->promise(), $loop);
        $this->assertCount(2, $data);
    }

    /**
     * Test get index fields.
     */
    public function testIndexGetFields()
    {
        $loop = Factory::create();
        $repository = $this->getFullRepository($loop);
        $repositoryReference = $this->createRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference, $this->createItems()));
        $this->await($repository->addItems($repositoryReference, [
            Item::createFromArray([
                'uuid' => [
                    'id' => '111',
                    'type' => 'lol',
                ],
                'metadata' => [
                    'field' => 'value',
                    'another_field' => 'value1',
                    'yet_another_field' => 1,
                ],
                'indexed_metadata' => [
                    'f1' => 1,
                    'f2' => 1.1,
                    'f3' => [
                        'id' => 'a',
                        'lol' => '1',
                    ],
                    'f4' => 'haha',
                ],
                'searchable_metadata' => [
                    's1' => 'v1',
                    's2' => 2,
                ],
            ]),
        ]));

        $indices = $this->await($repository->getIndices($repositoryReference));
        $this->assertEquals([
            'uuid.id' => 'string',
            'uuid.type' => 'string',
            'metadata.field' => 'string',
            'metadata.another_field' => 'string',
            'metadata.yet_another_field' => 'long',
            'indexed_metadata.f1' => 'long',
            'indexed_metadata.f2' => 'long',
            'indexed_metadata.f3' => 'object',
            'indexed_metadata.f4' => 'string',
            'searchable_metadata.s1' => 'string',
            'searchable_metadata.s2' => 'long',
        ], $indices[0]->getFields());
    }

    /**
     * Test repository reference selector.
     *
     * app1
     *    - index1 -> [1, 2]
     *    - index2
     * app2
     *    - index3 -> [1]
     *
     * @param string|null $appId
     * @param string|null $indexId
     * @param string[]    $resultIndexIds
     * @param string[]    $resultItemIds
     *
     * @dataProvider dataRepositoryReferenceSelector
     */
    public function testRepositoryReferenceSelector(
        ?string $appId,
        ?string $indexId,
        array $resultIndexIds,
        array $resultItemIds
    ) {
        $repository = $this->getFullRepository();
        $repositoryReference = $this->createAppRepositoryReference();
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID(), $this->createConfig()));
        $this->await($repository->createIndex($repositoryReference, $this->createIndexUUID('index2'), $this->createConfig()));
        $this->await($repository->addItems($this->createRepositoryReference('app1', 'index1'), $this->createItems()));

        $repositoryReference2 = $this->createRepositoryReference('app2', 'index3');
        $this->await($repository->createIndex($repositoryReference2, $this->createIndexUUID('index3'), $this->createConfig()));
        $this->await($repository->addItems($repositoryReference2, [$this->createItem('item3')]));

        $usableRepositoryReference = $this->createRepositoryReference($appId, $indexId);

        $indices = $this->await($repository->getIndices($usableRepositoryReference));
        $this->assertCount(\count($resultIndexIds), $indices);
        foreach ($indices as $index) {
            $indexIdNum = \str_replace('index', '', $index->getUUID()->composeUUID());
            $this->assertTrue(\in_array($indexIdNum, $resultIndexIds));
        }

        $result = $this->await($repository->query($usableRepositoryReference, $this->createQuery()));
        $items = $result->getItems();

        $this->assertCount(\count($resultItemIds), $items);
        foreach ($items as $item) {
            $itemIdNum = \str_replace('item', '', $item->getId());
            $this->assertTrue(\in_array($itemIdNum, $resultItemIds));
        }
    }

    /**
     * Data for repository reference selector.
     *
     * app1
     *    - index1 -> [1, 2]
     *    - index2
     * app2
     *    - index3 -> [3]
     */
    public function dataRepositoryReferenceSelector()
    {
        return [
            [null, null, ['1', '2', '3'], ['1', '2', '3']],
            ['*', null, ['1', '2', '3'], ['1', '2', '3']],
            ['*', '*', ['1', '2', '3'], ['1', '2', '3']],
            ['', '', ['1', '2', '3'], ['1', '2', '3']],
            ['app1', '', ['1', '2'], ['1', '2']],
            ['app2', '', ['3'], ['3']],
            ['app1,app2', '', ['1', '2', '3'], ['1', '2', '3']],
            ['app1,app2', '*', ['1', '2', '3'], ['1', '2', '3']],
            ['app1,app2', 'index1,index2,index3', ['1', '2', '3'], ['1', '2', '3']],
            ['app1', 'index3', [], []],
            ['app1', 'index2', ['2'], []],
        ];
    }

    /**
     * Create RepositoryReference.
     *
     * @param string|null $appId
     * @param string|null $indexId
     *
     * @return RepositoryReference
     */
    private function createRepositoryReference(
        ?string $appId = 'app1',
        ?string $indexId = 'index1'
    ): RepositoryReference {
        return \is_null($indexId)
            ? $this->createAppRepositoryReference($appId)
            : RepositoryReference::create(
                $this->createAppUUID($appId),
                $this->createIndexUUID($indexId)
            );
    }

    /**
     * Create RepositoryReference.
     *
     * @param string|null $appId
     *
     * @return RepositoryReference
     */
    private function createAppRepositoryReference(?string $appId = 'app1'): RepositoryReference
    {
        return \is_null($appId)
            ? RepositoryReference::create()
            : RepositoryReference::create($this->createAppUUID($appId));
    }

    /**
     * Create AppUUID.
     *
     * @param string $appId
     *
     * @return AppUUID
     */
    private function createAppUUID(string $appId = 'app1'): AppUUID
    {
        return AppUUID::createById($appId);
    }

    /**
     * Create IndexUUID.
     *
     * @param string $indexId
     *
     * @return IndexUUID
     */
    private function createIndexUUID(string $indexId = 'index1'): IndexUUID
    {
        return IndexUUID::createById($indexId);
    }

    /**
     * Create Items.
     *
     * @param string $item1Id
     * @param string $item2Id
     *
     * @return Item
     */
    private function createItems(
        string $item1Id = 'item1',
        string $item2Id = 'item2'
    ): array {
        return [
            $this->createItem($item1Id),
            $this->createItem($item2Id),
        ];
    }

    /**
     * Create Item.
     *
     * @param string $itemId
     *
     * @return Item
     */
    private function createItem(string $itemId = 'item1'): Item
    {
        return Item::create(
            $this->createItemUUID($itemId),
            [
                'field' => true,
                'another_field' => false,
            ]
        );
    }

    /**
     * Create ItemUUID.
     *
     * @param string $itemId
     *
     * @return ItemUUID
     */
    private function createItemUUID(string $itemId = 'item1'): ItemUUID
    {
        return new ItemUUID($itemId, 'type');
    }

    /**
     * Create config.
     *
     * @return ItemUUID
     */
    private function createConfig(): Config
    {
        return new Config();
    }

    /**
     * Create query.
     *
     * @return Query
     */
    private function createQuery(): Query
    {
        return Query::createMatchAll();
    }

    /**
     * Create query.
     *
     * @return Query
     */
    private function createMultiQuery(): Query
    {
        return Query::createMultiquery([
            $this->createQuery(),
            $this->createQuery(),
        ]);
    }
}
