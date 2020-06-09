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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\SearchesRepository;

use Apisearch\Server\Domain\Repository\SearchesRepository\InMemorySearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use React\EventLoop\LoopInterface;

/**
 * Class InMemorySearchesRepositoryTest.
 */
class InMemorySearchesRepositoryTest extends SearchesRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return SearchesRepository
     */
    public function getEmptyRepository(LoopInterface $loop): SearchesRepository
    {
        return new InMemorySearchesRepository();
    }
}
