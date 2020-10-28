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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\AppRepository;

use Apisearch\Server\Domain\Repository\AppRepository\ConfigRepository;
use Apisearch\Server\Domain\Repository\AppRepository\InMemoryConfigRepository;
use React\EventLoop\LoopInterface;

/**
 * Class InMemoryConfigRepositoryTest.
 */
class InMemoryConfigRepositoryTest extends ConfigRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return ConfigRepository
     */
    public function buildEmptyRepository(LoopInterface $loop): ConfigRepository
    {
        return new InMemoryConfigRepository();
    }
}
