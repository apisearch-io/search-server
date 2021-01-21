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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\UsageRepository;

use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class InMemoryUsageRepositoryTest.
 */
class InMemoryUsageRepositoryTest extends UsageRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    public function getEmptyRepository(LoopInterface $loop): UsageRepository
    {
        return new InMemoryUsageRepository();
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
        /*
         * @var InMemoryUsageRepository $repository
         */
        return $repository instanceof InMemoryUsageRepository
            ? $repository->getNumberOfRows()
            : resolve(0);
    }
}
