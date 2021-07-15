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

use Apisearch\Plugin\DBAL\Domain\SearchesRepository\ChunkSearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\InMemorySearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\SearchesRepository\SearchesRepositoryTest;
use React\EventLoop\LoopInterface;

/**
 * Class ChunkSearchesRepositoryTest.
 */
class ChunkSearchesRepositoryTest extends SearchesRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    public function getEmptyRepository(LoopInterface $loop): SearchesRepository
    {
        $repository = new ChunkSearchesRepository(
            new InMemorySearchesRepository(),
            DBALSearchesRepositoryTest::createEmptyRepository(
                DBALConnectionFactory::create($loop)
            ),
            $loop
        );

        $loop->addPeriodicTimer(.05, function () use ($repository) {
            $repository->flush();
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
}
