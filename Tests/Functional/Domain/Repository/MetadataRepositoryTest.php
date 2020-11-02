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
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Item;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query as QueryModel;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\CreateIndex;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\ImperativeEvent\LoadMetadata;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Query\Query;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class MetadataRepositoryTest.
 */
abstract class MetadataRepositoryTest extends ServiceFunctionalTest
{
    /**
     * Is distributed metadata respository.
     */
    abstract public function isDistributedMetadataRepository(): bool;

    /**
     * Test initial configuration repository state.
     */
    public function testInitialMetadataRepositoryState()
    {
        $this->assertNull(
            $this->getRepository()->get(RepositoryReference::createFromComposed('A_B'), 'key1')
        );
    }

    /**
     * Test set and get.
     */
    public function testSetAndGet()
    {
        $repository = $this->getRepository();
        $repositoryReference = RepositoryReference::createFromComposed('A_B');
        static::await($repository->set($repositoryReference, 'key1', 'value1'));
        $this->assertNull($repository->get($repositoryReference, 'key1'));
        $this->dispatchImperative(new LoadMetadata($repositoryReference));

        $this->assertEquals('value1', $repository->get($repositoryReference, 'key1'));
        $repository->delete(RepositoryReference::createFromComposed('A_'), 'key1');
        $this->dispatchImperative(new LoadMetadata($repositoryReference));
        $this->assertEquals('value1', $repository->get($repositoryReference, 'key1'));
        $repository->delete(RepositoryReference::createFromComposed('A_B'), 'key1');
        $this->dispatchImperative(new LoadMetadata($repositoryReference));
        $this->assertNull($repository->get($repositoryReference, 'key1'));
    }

    /**
     * Test metadata in indices results.
     */
    public function testMetadataInIndicesResult()
    {
        $repository = $this->getRepository();
        $repositoryReference = RepositoryReference::createFromComposed(static::$appId.'_'.static::$index);
        static::await($repository->set($repositoryReference, 'key1', 'value1'));
        $this->dispatchImperative(new LoadMetadata($repositoryReference));
        $index = $this->getPrincipalIndex();
        $this->assertEquals('value1', $index->getMetadataValue('stored_metadata')['key1']);
    }

    /**
     * Test token persistence on new service creation.
     */
    public function testNewServiceConfig()
    {
        if (!$this->isDistributedMetadataRepository()) {
            $this->markTestSkipped('Skipped. Testing a non-distributed adapter');

            return;
        }

        $newKernel1 = static::createNewKernel();
        $commandBus1 = $newKernel1->getContainer()->get('drift.command_bus.test');
        $queryBus1 = $newKernel1->getContainer()->get('drift.query_bus.test');
        $loop1 = $newKernel1->getContainer()->get('reactphp.event_loop');

        $repositoryReference = RepositoryReference::createFromComposed('app1_index1');
        $godToken = new Token(TokenUUID::createById(self::$godToken), AppUUID::createById('app1'));

        $createIndex = new CreateIndex(
            $repositoryReference,
            $godToken,
            IndexUUID::createById('index1'),
            Config::createEmpty()
        );

        static::await($commandBus1->execute($createIndex), $loop1);
        $item = Item::createFromArray([
            'uuid' => ['id' => 1, 'type' => 'type'],
            'metadata' => [],
            'indexed_metadata' => [
                'category' => [
                    'id' => 1,
                    'name' => 'cat1',
                    'level' => 'level1',
                ],
            ],
        ]);

        static::await($commandBus1->execute(new IndexItems($repositoryReference, $godToken, [$item])), $loop1);

        $result = static::await($queryBus1->ask(new Query($repositoryReference, $godToken, QueryModel::createMatchAll(), Origin::createEmpty(), '1')), $loop1);
        $this->assertEquals([
            'id' => 1,
            'name' => 'cat1',
            'level' => 'level1',
        ], $result->getFirstItem()->getIndexedMetadata()['category']);

        /**
         * We start the second kernel.
         * Preload event should preload everything.
         */
        $newKernel2 = static::createNewKernel();
        $queryBus2 = $newKernel2->getContainer()->get('drift.query_bus.test');
        $loop2 = $newKernel2->getContainer()->get('reactphp.event_loop');

        $result = static::await($queryBus2->ask(new Query($repositoryReference, $godToken, QueryModel::createMatchAll(), Origin::createEmpty(), '1')), $loop2);
        $this->assertEquals([
            'id' => 1,
            'name' => 'cat1',
            'level' => 'level1',
        ], $result->getFirstItem()->getIndexedMetadata()['category']);
    }

    /**
     * Get metadata repository.
     *
     * @return MetadataRepository
     */
    protected function getRepository(): MetadataRepository
    {
        return $this->get('apisearch_server.metadata_repository_test');
    }
}
