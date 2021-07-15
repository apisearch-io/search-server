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
use Apisearch\Plugin\DBAL\Domain\UsageRepository\DBALUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\UsageRepository\UsageRepositoryTest;
use function Clue\React\Block\await;
use Drift\DBAL\Result;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

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
        $repository = new ChunkUsageRepository(
            new InMemoryUsageRepository(),
            DBALUsageRepositoryTest::createEmptyRepository(
                DBALConnectionFactory::create($loop)
            ),
            $loop
        );

        $loop->addPeriodicTimer(.05, function () use ($repository) {
            $repository->flushLines();
        });

        return $repository;
    }

    /**
     * Seconds sleeping before query.
     *
     * @return int
     */
    public function microsecondsSleepingBeforeQuery(): int
    {
        return 100000;
    }

    /**
     * Test inserted rows.
     *
     * @return void
     */
    public function testInsertedRows(): void
    {
        $loop = Factory::create();
        $connection = DBALConnectionFactory::create($loop);
        $repository = new ChunkUsageRepository(
            new InMemoryUsageRepository(),
            DBALUsageRepositoryTest::createEmptyRepository($connection),
            $loop
        );

        $loop->addPeriodicTimer(.05, function () use ($repository) {
            $repository->flushLines();
        });

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

    /**
     * Get number of rows.
     *
     * @param UsageRepository $repository
     *
     * @return PromiseInterface<int>
     */
    public function getNumberOfRows(UsageRepository $repository): PromiseInterface
    {
        /**
         * @var DBALUsageRepository
         */
        $connection = $repository->getConnection();
        $tableName = $repository->getTableName();

        return $connection
            ->query($connection
                ->createQueryBuilder()
                ->select('count(*) as count')
                ->from($tableName, 'u')
            )
            ->then(function (Result $result) {
                return \intval($result->fetchFirstRow()['count']);
            });
    }
}
