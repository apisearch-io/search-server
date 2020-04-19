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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Plugin\DBAL\Domain\UsageRepository\ChunkUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\UsageRepository\UsageRepositoryTest;
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
            DBALUsageRepositoryTest::createEmptyRepository($loop),
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
}
