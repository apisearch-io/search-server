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

use Apisearch\Query\Filter;
use Apisearch\Query\Query;

/**
 * Class ItemsDeletionByQueryTest.
 */
trait ItemsDeletionByQueryTest
{
    /**
     * Test item deletions by query.
     *
     * @return void
     */
    public function testItemDeletionsByQuery(): void
    {
        $this->deleteItemsByQuery(Query::createMatchAll());
        $this->assertCount(0, $this->query(Query::createMatchAll())->getItems());

        static::resetScenario();
    }

    /**
     * Test item deletion with filter.
     *
     * @return void
     */
    public function testItemDeletionByQueryWithFilter(): void
    {
        $this->deleteItemsByQuery(Query::createMatchAll()->filterByRange('cheap', 'price', [], ['0..1000']));
        $this->assertCount(2, $this->query(Query::createMatchAll())->getItems());

        static::resetScenario();
    }

    /**
     * Test item deletion with filter.
     *
     * @return void
     */
    public function testItemDeletionByQueryLimit(): void
    {
        $this->deleteItemsByQuery(Query::create('', 1, 1)->filterByRange('cheap', 'price', [], ['0..1501']));
        $this->assertCount(1, $this->query(Query::createMatchAll())->getItems());

        static::resetScenario();
    }

    /**
     * Test item deletion with EXCLUDE filter.
     *
     * @return void
     */
    public function testItemDeletionByExclude(): void
    {
        $this->deleteItemsByQuery(Query::create('', 1, 1)->filterBy('but_pink', 'color', ['pink'], Filter::EXCLUDE));
        $this->assertCount(1, $this->query(Query::createMatchAll())->getItems());
        $this->deleteItemsByQuery(Query::create('', 1, 1)->filterBy('another', 'non-existing', ['what'], Filter::EXCLUDE));
        $this->assertCount(0, $this->query(Query::createMatchAll())->getItems());

        static::resetScenario();
    }
}
