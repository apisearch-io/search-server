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

namespace Apisearch\Server\Tests\Unit\Domain\Repository\PurchaseRepository;

use Apisearch\Server\Domain\Repository\PurchaseRepository\InMemoryPurchaseRepository;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseRepository;
use React\EventLoop\LoopInterface;

/**
 * Class InMemoryPurchaseRepositoryTest.
 */
class InMemoryPurchaseRepositoryTest extends PurchaseRepositoryTest
{
    /**
     * @param LoopInterface $loop
     *
     * @return PurchaseRepository
     */
    public function getEmptyRepository(LoopInterface $loop): PurchaseRepository
    {
        return new InMemoryPurchaseRepository();
    }
}
