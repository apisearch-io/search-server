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

use Apisearch\Server\Domain\Repository\LogRepository\InMemoryLogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use React\EventLoop\LoopInterface;

/**
 * Class InMemoryLogRepositoryTest.
 */
class InMemoryLogRepositoryTest extends LogRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return LogRepository
     */
    public function getEmptyRepository(LoopInterface $loop): LogRepository
    {
        return new InMemoryLogRepository();
    }
}
