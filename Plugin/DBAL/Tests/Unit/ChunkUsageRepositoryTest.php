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

use Apisearch\Plugin\DBAL\Domain\UsageRepository\ChunkUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\UsageRepository\UsageRepositoryTest;
use function Clue\React\Block\await;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class ChunkUsageRepositoryTest.
 */
class ChunkUsageRepositoryTest extends UsageRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    public function getEmptyRepository(LoopInterface $loop): UsageRepository
    {
        return new ChunkUsageRepository(
            new InMemoryUsageRepository(),
            DBALUsageRepositoryTest::createEmptyRepository(
                DBALUsageRepositoryTest::createConnection($loop)
            ),
            $loop,
            1
        );
    }

    /**
     * Seconds sleeping before query.
     *
     * @return int
     */
    public function secondsSleepingBeforeQuery(): int
    {
        return 2;
    }

    /**
     * Test inserted rows.
     */
    public function testInsertedRows()
    {
        $loop = Factory::create();
        $connection = DBALUsageRepositoryTest::createConnection($loop);
        $repository = new ChunkUsageRepository(
            new InMemoryUsageRepository(),
            DBALUsageRepositoryTest::createEmptyRepository($connection),
            $loop,
            1
        );

        $this->setUpEnvironment($repository, $loop);

        $rows = await($connection->findBy('uses'), $loop);
        $this->assertCount(23, $rows);
        $this->assertEquals(63, $rows[0]['n']);
        $this->assertEquals(12, $rows[1]['n']);
        $this->assertEquals(23, $rows[2]['n']);
        $this->assertEquals(79, $rows[3]['n']);
        $this->assertEquals(23, $rows[4]['n']);
        $this->assertEquals(77, $rows[5]['n']);
    }
}