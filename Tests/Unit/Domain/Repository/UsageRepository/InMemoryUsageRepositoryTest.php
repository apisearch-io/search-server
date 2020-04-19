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
}
