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

use Apisearch\Plugin\DBAL\Domain\InteractionRepository\ChunkInteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InMemoryInteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use Apisearch\Server\Tests\Unit\Domain\Repository\InteractionRepository\InteractionRepositoryTest;
use React\EventLoop\LoopInterface;

/**
 * Class ChunkInteractionRepositoryTest.
 */
class ChunkInteractionRepositoryTest extends InteractionRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    public function getEmptyRepository(LoopInterface $loop): InteractionRepository
    {
        $repository = new ChunkInteractionRepository(
            new InMemoryInteractionRepository(),
            DBALInteractionRepositoryTest::createEmptyRepository(
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
