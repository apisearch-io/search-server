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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\InteractionRepository;

use Apisearch\Server\Domain\Repository\InteractionRepository\InMemoryInteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use React\EventLoop\LoopInterface;

/**
 * Class InMemoryInteractionRepositoryTest.
 */
class InMemoryInteractionRepositoryTest extends InteractionRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return InteractionRepository
     */
    public function getEmptyRepository(LoopInterface $loop): InteractionRepository
    {
        return new InMemoryInteractionRepository();
    }
}
