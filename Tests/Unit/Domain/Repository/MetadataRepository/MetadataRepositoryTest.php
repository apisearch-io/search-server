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

/**
 * Class MetadataRepositoryTest.
 */
abstract class MetadataRepositoryTest extends BaseUnitTest
{
    /**
     * @return MetadataRepository
     */
    abstract public function buildEmptyRepository(): MetadataRepository;

    /**
     * Test get on empty repository.
     */
    public function testGetOnEmpty()
    {
        $repository = $this->buildEmptyRepository();
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
    }

    /**
     * Test get on existing value.
     */
    public function testGetOnExisting()
    {
        $repository = $this->buildEmptyRepository();
        $repository->set(RepositoryReference::createFromComposed('a_'), 'A', 'V1');
        $repository->set(RepositoryReference::createFromComposed('a_b'), 'A', 'V2');
        $repository->set(RepositoryReference::createFromComposed('a_b'), 'B', 'V3');
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));

        $this->awaitAll([
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_')),
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_b')),
        ]);

        $this->assertEquals('V1', $repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertEquals('V2', $repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertEquals('V3', $repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));

        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'B'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_c'), 'A'));
    }

    /**
     * Test delete.
     */
    public function testDelete()
    {
        $repository = $this->buildEmptyRepository();
        $repository->set(RepositoryReference::createFromComposed('a_'), 'A', 'V1');
        $repository->set(RepositoryReference::createFromComposed('a_b'), 'A', 'V2');
        $repository->set(RepositoryReference::createFromComposed('a_b'), 'B', 'V3');

        $this->awaitAll([
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_')),
            $repository->forceLoadMetadata(RepositoryReference::createFromComposed('a_b')),
        ]);

        $repository->delete(RepositoryReference::createFromComposed('a_'), 'A');
        $repository->delete(RepositoryReference::createFromComposed('a_b'), 'A');
        $repository->delete(RepositoryReference::createFromComposed('a_b'), 'B');

        $this->assertEquals('V1', $repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertEquals('V2', $repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertEquals('V3', $repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));

        $this->await($repository->forceLoadAllMetadata());

        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'A'));
        $this->assertNull($repository->get(RepositoryReference::createFromComposed('a_b'), 'B'));
    }
}
