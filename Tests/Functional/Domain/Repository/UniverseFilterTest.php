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

use Apisearch\Geo\CoordinateAndDistance;
use Apisearch\Model\Coordinate;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;

/**
 * Trait UniverseFilterTest.
 */
trait UniverseFilterTest
{
    /**
     * Test filtering universe by type.
     *
     * @return void
     */
    public function testFilterUniverseByType(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->filterUniverseByTypes(['product'])
                ->aggregateBy('category', 'category_data', Filter::AT_LEAST_ONE)
        );

        $this->assertCount(4, $result->getAggregation('category'));
    }

    /**
     * Test filtering universe by ids.
     *
     * @return void
     */
    public function testFilterUniverseById(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->filterUniverseByIds(['2', '3'])
                ->aggregateBy('category', 'category_data', Filter::AT_LEAST_ONE)
        );

        $this->assertCount(3, $result->getAggregation('category'));
    }

    /**
     * Test filtering universe by ids.
     *
     * @return void
     */
    public function testFilterUniverse(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->filterUniverseBy('color', ['yellow'], Filter::AT_LEAST_ONE)
                ->aggregateBy('stores', 'stores', Filter::AT_LEAST_ONE)
                ->enableSuggestions()
        );

        $this->assertCount(3, $result->getAggregation('stores'));
        $this->assertCount(2, $result->getItems());
    }

    /**
     * Test filtering universe by range.
     *
     * @return void
     */
    public function testFilterUniverserByRange(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->filterUniverseByRange('price', ['10..1000'], Filter::AT_LEAST_ONE)
        );
        $this->assertCount(2, $result->getItems());

        $result = $this->query(
            Query::createMatchAll()
                ->filterUniverseByRange('price', ['5..15', '1000..2001'], Filter::AT_LEAST_ONE)
        );
        $this->assertCount(3, $result->getItems());

        $result = $this->query(
            Query::createMatchAll()
                ->filterUniverseByRange('price', ['5..', '..20000'], Filter::AT_LEAST_ONE)
        );
        $this->assertCount(5, $result->getItems());
    }

    /**
     * Test filter universe by location.
     *
     * @return void
     */
    public function testFilterUniverseByLocation(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->filterUniverseByLocation(new CoordinateAndDistance(
                    new Coordinate(45.0, 45.0),
                    '1180km'
                ))
        );
        $this->assertCount(2, $result->getItems());
    }

    /**
     * Test filter by rangde dates.
     *
     * @return void
     */
    public function testUniverseDateRangeFilter(): void
    {
        $this->assertCount(
            4,
            $this->buildCreatedAtUniverseFilteredResult('2010-01-01..2021-01-01')->getItems()
        );

        $this->assertResults(
            $this->buildCreatedAtUniverseFilteredResult('2010-01-01..2021-01-01'),
            ['?1', '?2', '?3', '?4', '!5']
        );

        $this->assertResults(
            $this->buildCreatedAtUniverseFilteredResult('..2021-01-01'),
            ['?1', '?2', '?3', '?4', '!5']
        );

        $this->assertCount(
            2,
            $this->buildCreatedAtUniverseFilteredResult('..2020-03-03')->getItems()
        );

        $this->assertCount(
            3,
            $this->buildCreatedAtUniverseFilteredResult('..2020-03-03T23:59:59Z')->getItems()
        );

        $this->assertCount(
            2,
            $this->buildCreatedAtUniverseFilteredResult('2020-02-02..2020-04-04')->getItems()
        );

        $this->assertCount(
            3,
            $this->buildCreatedAtUniverseFilteredResult('2020-02-02..')->getItems()
        );

        $this->assertCount(
            5,
            $this->buildCreatedAtUniverseFilteredResult('..')->getItems()
        );

        $this->assertCount(
            1,
            $this->query(Query::createMatchAll()
                ->filterUniverseByDateRange('created_at', ['2020-02-02..2020-04-04'], Filter::AT_LEAST_ONE)
                ->filterByDateRange('created_at', 'created_at', [], ['2020-03-03..2020-04-04'], Filter::AT_LEAST_ONE, false)
            )->getItems()
        );
    }
}
