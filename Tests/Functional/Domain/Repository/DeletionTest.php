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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class DeletionTest.
 */
trait DeletionTest
{
    /**
     * Test item deletions.
     *
     * @return void
     */
    public function testItemDeletions(): void
    {
        $this->deleteItems([new ItemUUID('1', 'product')]);
        $this->assertNbItems(4);

        $this->deleteItems([new ItemUUID('1', 'product')]);
        $this->assertNbItems(4);

        $this->deleteItems([new ItemUUID('75894379573', 'product')]);
        $this->assertNbItems(4);

        $this->deleteItems([new ItemUUID('5', 'product')]);
        $this->assertNbItems(4);

        $this->deleteItems([new ItemUUID('5', 'gum')]);
        $this->assertNbItems(3);

        /*
         * Reseting scenario for next calls.
         */
        self::resetScenario();
    }

    /**
     * Check nb items.
     *
     * @param int $nb
     *
     * @return void
     */
    private function assertNbItems(int $nb): void
    {
        $this->assertCount($nb, $this
            ->query(Query::createMatchAll())
            ->getItems()
        );
    }
}
