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
use Apisearch\Query\Range;

/**
 * Class RangeFiltersTest.
 */
trait RangeFiltersTest
{
    /**
     * Test filter by price range.
     *
     * @return void
     */
    public function testPriceRangeFilter(): void
    {
        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['1000..2000'])),
            ['!1', '?2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['1000..2001'])->filterByTypes(['book'])),
            ['!1', '!2', '?3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['900..1900'])),
            ['?1', '?2', '!3', '!4', '!5']
        );

        $this->assertEmpty(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['100..200']))->getItems()
        );

        $this->assertEmpty(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['0..1']))->getItems()
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['0..'])),
            ['?1', '?2', '?3', '?4', '?5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['1..'])),
            ['?1', '?2', '?3', '?4', '?5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByRange('price', 'price', [], ['0..0'])->filterByRange('price', 'price', [], ['0..'])),
            ['?1', '?2', '?3', '?4', '?5']
        );

        $this->assertEmpty(
            $this->query(
                Query::createMatchAll()->filterByRange('price', 'price', [], ['0..']),
                self::$anotherAppId
            )->getItems()
        );
    }

    /**
     * Test filter by range dates.
     *
     * @return void
     */
    public function testDateRangeFilter(): void
    {
        $this->assertCount(
            4,
            $this->buildCreatedAtFilteredResult('2010-01-01T00:00:00+00:00..2021-01-01T00:00:00+00:00')->getItems()
        );

        $this->assertResults(
            $this->buildCreatedAtFilteredResult('2010-01-01T00:00:00+00:00..2021-01-01T00:00:00+00:00'),
            ['?1', '?2', '?3', '?4', '!5']
        );

        $this->assertResults(
            $this->buildCreatedAtFilteredResult('..2021-01-01T00:00:00+00:00'),
            ['?1', '?2', '?3', '?4', '!5']
        );

        $this->assertCount(
            2,
            $this->buildCreatedAtFilteredResult('..2020-03-03T00:00:00+00:00')->getItems()
        );

        $this->assertCount(
            3,
            $this->buildCreatedAtFilteredResult('..2020-03-03T00:00:01+00:00')->getItems()
        );

        $this->assertCount(
            2,
            $this->buildCreatedAtFilteredResult('2020-02-02T00:00:00+00:00..2020-04-04T00:00:00+00:00')->getItems()
        );

        $this->assertCount(
            3,
            $this->buildCreatedAtFilteredResult('2020-02-02T00:00:00+00:00..')->getItems()
        );

        $this->assertCount(
            5,
            $this->buildCreatedAtFilteredResult('..')->getItems()
        );
    }

    /**
     * Test range with distribution.
     */
    public function testRangeWithDistribution()
    {
        $priceDistributionCounters = $this->query(
            Query::createMatchAll()
                ->disableResults()
                ->aggregateByRange('price_distribution', 'price', Range::createRanges(0, 3000, 1000), Filter::AT_LEAST_ONE)
        )->getAggregation('price_distribution')->getCounters();

        $this->assertCount(3, $priceDistributionCounters);
        $this->assertEquals(3, $priceDistributionCounters['0..1000']->getN());
        $this->assertEquals(1, $priceDistributionCounters['1000..2000']->getN());
        $this->assertEquals(1, $priceDistributionCounters['2000..3000']->getN());
    }

    /**
     * Test min-max aggregation in range.
     */
    public function testRangeWithMinAndMax()
    {
        $priceMinMax = $this->query(
            Query::createMatchAll()
                ->disableResults()
                ->aggregateByRange('price_min_max', 'price', ['..'], Filter::AT_LEAST_ONE, Filter::TYPE_RANGE_WITH_MIN_MAX)
        )->getAggregation('price_min_max');

        $this->assertEquals(7, $priceMinMax->getMetadata()['min']);
        $this->assertEquals(2000, $priceMinMax->getMetadata()['max']);

        $priceMinMax = $this->query(
            Query::createMatchAll()
                ->disableResults()
                ->filterBy('price', 'price', [7], Filter::MUST_ALL, false)
                ->aggregateByRange('price_min_max', 'price', ['..'], Filter::AT_LEAST_ONE, Filter::TYPE_RANGE_WITH_MIN_MAX)
        )->getAggregation('price_min_max');

        $this->assertEquals(7, $priceMinMax->getMetadata()['min']);
        $this->assertEquals(2000, $priceMinMax->getMetadata()['max']);

        $priceMinMax = $this->query(
            Query::createMatchAll()
                ->disableResults()
                ->filterUniverseBy('price', [7], Filter::MUST_ALL)
                ->aggregateByRange('price_min_max', 'price', ['..'], Filter::AT_LEAST_ONE, Filter::TYPE_RANGE_WITH_MIN_MAX)
        )->getAggregation('price_min_max');

        $this->assertEquals(7, $priceMinMax->getMetadata()['min']);
        $this->assertEquals(7, $priceMinMax->getMetadata()['max']);

        $priceMinMax = $this->query(
            Query::createMatchAll()
                ->disableResults()
                ->filterUniverseByRange('price', ['500..1501'], Filter::MUST_ALL)
                ->aggregateByRange('price_min_max', 'price', ['..'], Filter::AT_LEAST_ONE, Filter::TYPE_RANGE_WITH_MIN_MAX)
        )->getAggregation('price_min_max');

        $this->assertEquals(700, $priceMinMax->getMetadata()['min']);
        $this->assertEquals(1500, $priceMinMax->getMetadata()['max']);
    }

    /**
     * Test min-max aggregation in time range.
     */
    public function testDateRangeWithMinAndMax()
    {
        $createdAtMinMax = $this->query(
            Query::createMatchAll()
                ->disableResults()
                ->aggregateByRange('created_at_min_max', 'created_at', ['..'], Filter::AT_LEAST_ONE, Filter::TYPE_RANGE_WITH_MIN_MAX)
        )->getAggregation('created_at_min_max');

        $this->assertEquals(1577836800000, $createdAtMinMax->getMetadata()['min']);
        $this->assertEquals(1585958400000, $createdAtMinMax->getMetadata()['max']);
    }
}
