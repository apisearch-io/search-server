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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\LogRepository;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Repository\LogRepository\LogFilter;
use Apisearch\Server\Domain\Repository\LogRepository\LogMapper;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

/**
 * Class LogRepositoryTest.
 */
abstract class LogRepositoryTest extends BaseUnitTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return LogRepository
     */
    abstract public function getEmptyRepository(LoopInterface $loop): LogRepository;

    /**
     * Seconds sleeping before query.
     *
     * @return int
     */
    public function microsecondsSleepingBeforeQuery(): int
    {
        return 0;
    }

    /**
     * Test empty.
     *
     * @return void
     */
    public function testEmpty(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $this->assertEmpty($this->await($repository->getLogs(LogFilter::create($repositoryReference)), $loop));
    }

    /**
     * Test empty.
     *
     * @return void
     */
    public function testSimple(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $repository->log($repositoryReference, new DateTime(), 1, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(1, '1', true));
        $repository->log($repositoryReference, new DateTime(), 1, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $repository->log($repositoryReference, new DateTime(), 10, LogMapper::INDEX_WAS_EXPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)), $loop));
    }

    /**
     * Test filter by datetime.
     *
     * @return void
     */
    public function testFilterByDateTime(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $repository->log($repositoryReference, new DateTime(), 1, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(1, '1', true));
        $repository->log($repositoryReference, new DateTime(), 1, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $repository->log($repositoryReference, new DateTime(), 10, LogMapper::INDEX_WAS_EXPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $this->assertCount(0, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->from((new DateTime())->modify('+1 day'))), $loop));
        $this->assertCount(0, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->to((new DateTime())->modify('-1 day'))), $loop));
        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->to((new DateTime())->modify('+1 day'))), $loop));
        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->from((new DateTime())->modify('-1 day'))), $loop));
        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)
            ->from((new DateTime())->modify('-1 day'))
            ->to((new DateTime())->modify('+1 day'))
        ), $loop));

        $repository->log($repositoryReference, (new DateTime())->modify('+2 day'), 10, LogMapper::INDEX_WAS_EXPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);
        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)
            ->from((new DateTime())->modify('-1 day'))
            ->to((new DateTime())->modify('+1 day'))
        ), $loop));

        $this->assertCount(4, $this->await($repository->getLogs(LogFilter::create($repositoryReference)
            ->from((new DateTime())->modify('-1 day'))
            ->to((new DateTime())->modify('+3 days'))
        ), $loop));

        $this->assertCount(1, $this->await($repository->getLogs(LogFilter::create($repositoryReference)
            ->from((new DateTime())->modify('+1 minute'))
            ->to((new DateTime())->modify('+3 days'))
        ), $loop));

        $this->assertCount(0, $this->await($repository->getLogs(LogFilter::create($repositoryReference)
            ->from((new DateTime())->modify('+5 days'))
            ->to((new DateTime())->modify('+10 days'))
        ), $loop));
    }

    /**
     * Test filter by types.
     *
     * @return void
     */
    public function testFilterByTypes(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $repository->log($repositoryReference, new DateTime(), 1, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(1, '1', true));
        $repository->log($repositoryReference, new DateTime(), 1, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $repository->log($repositoryReference, new DateTime(), 10, LogMapper::INDEX_WAS_EXPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $this->usleep($this->microsecondsSleepingBeforeQuery(), $loop);

        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->fromTypes([])), $loop));
        $this->assertCount(2, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->fromTypes([LogMapper::INDEX_WAS_IMPORTED])), $loop));
        $this->assertCount(1, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->fromTypes([LogMapper::INDEX_WAS_EXPORTED])), $loop));
        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->fromTypes([
            LogMapper::INDEX_WAS_IMPORTED,
            LogMapper::INDEX_WAS_EXPORTED,
        ])), $loop));
    }

    public function testPagination(): void
    {
        $loop = Factory::create();
        $repository = $this->getEmptyRepository($loop);
        $repositoryReference = $this->getDefaultRepositoryReference();
        $repository->log($repositoryReference, new DateTime(), 1, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(1, '1', true));
        $repository->log($repositoryReference, new DateTime(), 2, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $repository->log($repositoryReference, new DateTime(), 3, LogMapper::INDEX_WAS_EXPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $repository->log($repositoryReference, new DateTime(), 4, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(1, '1', true));
        $repository->log($repositoryReference, new DateTime(), 5, LogMapper::INDEX_WAS_IMPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));
        $repository->log($repositoryReference, new DateTime(), 6, LogMapper::INDEX_WAS_EXPORTED, LogMapper::createIndexWasImportedLogParams(10, '2', false));

        $this->assertCount(6, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(0, 0)), $loop));
        $this->assertCount(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(3, 1)), $loop));
        $this->assertEquals(1, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(3, 1)), $loop)[0]->getLog()->getN());
        $this->assertEquals(3, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(3, 1)), $loop)[2]->getLog()->getN());

        $this->assertCount(2, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(4, 2)), $loop));
        $this->assertEquals(5, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(4, 2)), $loop)[0]->getLog()->getN());
        $this->assertEquals(6, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(4, 2)), $loop)[1]->getLog()->getN());

        $this->assertCount(2, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(2, 3)), $loop));
        $this->assertEquals(5, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(4, 2)), $loop)[0]->getLog()->getN());
        $this->assertEquals(6, $this->await($repository->getLogs(LogFilter::create($repositoryReference)->paginate(4, 2)), $loop)[1]->getLog()->getN());
    }

    /**
     * @return RepositoryReference
     */
    private function getDefaultRepositoryReference(): RepositoryReference
    {
        return RepositoryReference::createFromComposed('a_b');
    }
}
