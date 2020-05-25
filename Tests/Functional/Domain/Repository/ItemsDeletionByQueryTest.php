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

use Apisearch\Query\Query;

/**
 * Class ItemsDeletionByQueryTest.
 */
trait ItemsDeletionByQueryTest
{
    /**
     * Test item deletions by query.
     */
    public function testItemDeletionsByQuery()
    {
        $this->deleteItemsByQuery(Query::createMatchAll());
        $this->assertCount(0, $this->query(Query::createMatchAll())->getItems());

        static::resetScenario();
    }

    /**
     * Test item deletion with filter.
     */
    public function testItemDeletionByQueryWithFilter()
    {
        $this->deleteItemsByQuery(Query::createMatchAll()->filterByRange('cheap', 'price', [], ['0..1000']));
        $this->assertCount(2, $this->query(Query::createMatchAll())->getItems());

        static::resetScenario();
    }

    /**
     * Test item deletion with filter.
     */
    public function testItemDeletionByQueryLimit()
    {
        $this->deleteItemsByQuery(Query::create('', 1, 1)->filterByRange('cheap', 'price', [], ['0..1501']));
        $this->assertCount(1, $this->query(Query::createMatchAll())->getItems());

        static::resetScenario();
    }
}
