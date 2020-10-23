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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\MetadataRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\MetadataRepository\MetadataRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use Apisearch\Server\Tests\Unit\Domain\Repository\MetadataRepository\Mock\AClass;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class MetadataRepositoryTest.
 */
abstract class MetadataRepositoryTest extends BaseUnitTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return MetadataRepository
     */
    abstract public function buildEmptyRepository(LoopInterface $loop): MetadataRepository;

    /**
     * Test get on empty repository.
     */
    public function testGetOnEmpty()
    {
        $loop = Factory::create();
        $repository = $this->buildEmptyRepository($loop);
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
    }

    /**
     * Test get on existing value.
     */
    public function testGetOnExisting()
    {
        $loop = Factory::create();
        $repository = $this->buildEmptyRepository($loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_'), 'A', 'V1'), $loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_b'), 'A', 'V2'), $loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_b'), 'B', 'V3'), $loop);
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));

        $this->awaitAll([
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_')),
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_b')),
        ], $loop);

        $this->assertEquals('V1', $repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertEquals('V2', $repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertEquals('V3', $repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));

        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'B'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_c'), 'A'));

        $this->await($repository->set(RepositoryReference::createFromComposed('a_'), 'Y', 'Z1'), $loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_b'), 'Z', 'Z2'), $loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_b'), 'B', 'V4'), $loop);

        $this->awaitAll([
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_')),
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_b')),
        ], $loop);

        $this->assertEquals('V1', $repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertEquals('Z1', $repository->get(RepositoryReference::createFromComposed('a_'), 'Y'));
        $this->assertEquals('V2', $repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertEquals('V4', $repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));
        $this->assertEquals('Z2', $repository->get(RepositoryReference::createFromComposed('a_b'), 'Z'));
    }

    /**
     * Test delete.
     */
    public function testDelete()
    {
        $loop = Factory::create();
        $repository = $this->buildEmptyRepository($loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_'), 'A', 'V1'), $loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_b'), 'A', 'V2'), $loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_b'), 'B', 'V3'), $loop);

        $this->awaitAll([
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_')),
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_b')),
        ], $loop);

        $this->await($repository->delete(RepositoryReference::createFromComposed('a_'), 'A'), $loop);
        $this->await($repository->delete(RepositoryReference::createFromComposed('a_b'), 'A'), $loop);
        $this->await($repository->delete(RepositoryReference::createFromComposed('a_b'), 'B'), $loop);

        $this->assertEquals('V1', $repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertEquals('V2', $repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertEquals('V3', $repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));

        $this->await($repository->forceLoadAllMetadata(), $loop);

        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));
    }

    /**
     * Test serializable objects.
     */
    public function testSerializableObjects()
    {
        $loop = Factory::create();
        $repository = $this->buildEmptyRepository($loop);
        $this->await($repository->set(RepositoryReference::createFromComposed('a_'), 'A', new AClass('engonga')), $loop);
        $this->await($repository->forceLoadAllMetadata(), $loop);
        $this->assertEquals('engonga', $repository->get(RepositoryReference::createFromComposed('a_'), 'A')->getValue());
    }
}
