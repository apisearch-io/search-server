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

use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Model\Metadata;
use Apisearch\Query\Aggregation;
use Apisearch\Query\Filter;
use Apisearch\Query\Query;

/**
 * Class AggregationsTest.
 */
trait AggregationsTest
{
    /**
     * Test aggregation with several fields.
     */
    public function testAuthorMustAllAggregations()
    {
        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'author',
                        'author_data',
                        Filter::MUST_ALL
                    )
            )
            ->getAggregations();

        $this->assertCount(3, $aggregations->getAggregation('author')->getCounters());
    }

    /**
     * Test basic aggregations.
     */
    public function testBasicAggregations()
    {
        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->FilterBy('color', 'color', ['pink'], Filter::AT_LEAST_ONE)
            )
            ->getAggregations();

        $aggregation = $aggregations->getAggregation('color');
        $this->assertCount(4, $aggregation->getCounters());
        $this->assertSame(
            1,
                $aggregation
                ->getCounter('pink')
                ->getN()
        );

        $this->assertSame(
            2,
                $aggregation
                ->getCounter('yellow')
                ->getN()
        );
    }

    /**
     * Test aggregations with null value.
     */
    public function testNullAggregation()
    {
        $aggregations = $this->query(
            Query::createMatchAll()
                ->FilterBy('nonexistent', 'nonexistent', [])
        )
        ->getAggregations();

        $this->assertEmpty($aggregations
            ->getAggregation('nonexistent')
            ->getCounters()
        );
    }

    /**
     * Test disable aggregations.
     */
    public function testDisableAggregations()
    {
        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->FilterBy('color', 'color', ['1'], Filter::AT_LEAST_ONE)
                    ->disableAggregations()
            )
            ->getAggregations();

        $this->assertNull($aggregations);
    }

    /**
     * Test editorial.
     */
    public function testEditorialAggregations()
    {
        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'editorial',
                        'editorial_data',
                        Filter::AT_LEAST_ONE
                    )
            )
            ->getAggregations();

        $this->assertCount(3, $aggregations->getAggregation('editorial')->getCounters());
    }

    /**
     * Test aggregation with one to many field.
     */
    public function testSimpleOneToManyAggregations()
    {
        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'stores',
                        'stores',
                        Filter::AT_LEAST_ONE
                    )
            )
            ->getAggregations();

        $this->assertCount(4, $aggregations->getAggregation('stores')->getCounters());
    }

    /**
     * Test aggregation with several fields.
     */
    public function testAuthorAggregations()
    {
        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'author',
                        'author_data',
                        Filter::AT_LEAST_ONE
                    )
            )
            ->getAggregations();

        $this->assertCount(3, $aggregations->getAggregation('author')->getCounters());
    }

    /**
     * Test aggregation with metadata format conversion.
     */
    public function testAggregationWithMetadataFormatConversion()
    {
        $this->indexItems([Item::create(
            new ItemUUID('1', 'testing'),
            [],
            [
                'author_data' => [
                    0 => Metadata::toMetadata([
                        'id' => 777,
                        'name' => 'Engonga',
                        'last_name' => 'Efervescencio',
                    ]),
                ],
            ]
        )]);

        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'author',
                        'author_data',
                        Filter::AT_LEAST_ONE
                    )
            )
            ->getAggregations();

        $this->assertCount(4, $aggregations->getAggregation('author')->getCounters());

        /*
         * Reseting scenario for next calls.
         */
        self::resetScenario();
    }

    /**
     * Test leveled aggregations.
     */
    public function testLeveledAggregations()
    {
        $aggregation = $this
            ->query(
                Query::createMatchAll()
                    ->aggregateBy('category', 'category_data', Filter::MUST_ALL_WITH_LEVELS)
            )
            ->getAggregation('category');
        $this->assertCount(2, $aggregation->getCounters());
        $this->assertTrue(array_key_exists('1', $aggregation->getCounters()));
        $this->assertTrue(array_key_exists('7', $aggregation->getCounters()));

        $aggregation = $this
            ->query(
                Query::createMatchAll()
                    ->FilterBy('category', 'category', ['1'], Filter::MUST_ALL_WITH_LEVELS)
                    ->aggregateBy('category', 'category_data', Filter::MUST_ALL_WITH_LEVELS)
            )
            ->getAggregation('category');
        $this->assertCount(2, $aggregation->getCounters());
        $this->assertTrue(array_key_exists('2', $aggregation->getCounters()));
        $this->assertTrue(array_key_exists('5', $aggregation->getCounters()));

        $aggregation = $this
            ->query(
                Query::createMatchAll()
                    ->FilterBy('category', 'category', ['2'], Filter::MUST_ALL_WITH_LEVELS)
                    ->aggregateBy('category', 'category_data', Filter::MUST_ALL_WITH_LEVELS)
            )
            ->getAggregation('category');
        $this->assertCount(2, $aggregation->getCounters());
        $this->assertTrue(array_key_exists('3', $aggregation->getCounters()));
        $this->assertTrue(array_key_exists('4', $aggregation->getCounters()));
    }

    /**
     * Aggregate by date.
     */
    public function testDateRangeAggregations()
    {
        $this->assertCount(
            1,
            $this->query(Query::createMatchAll()
                ->FilterUniverseByDateRange('created_at', ['2020-02-02..2020-04-04'], Filter::AT_LEAST_ONE)
                ->aggregateByDateRange('created_at', 'created_at', ['2020-03-03..2020-04-04'], Filter::AT_LEAST_ONE)
            )->getAggregation('created_at')
        );

        $this->assertCount(
            2,
            $this->query(Query::createMatchAll()
                ->FilterUniverseByDateRange('created_at', ['2020-02-02..2020-04-04'], Filter::AT_LEAST_ONE)
                ->aggregateByDateRange('created_at', 'created_at', ['2020-02-02..2020-03-03', '2020-03-03..2020-04-04'], Filter::AT_LEAST_ONE)
            )->getAggregation('created_at')
        );
    }

    /**
     * Aggregate by price.
     */
    public function testRangeAggregations()
    {
        static::resetScenario();
        $counters = $this->query(Query::createMatchAll()
            ->aggregateByRange('price', 'price', [
                '-100..100',
                '..100',
                '100..500',
                '500..1000',
                '1000..1700',
                '1700..',
                '0..',
                '..0'
            ], Filter::AT_LEAST_ONE)
        )->getAggregation('price')->getCounters();

        $this->assertEquals(1, $counters['-100..100']->getN());
        $this->assertEquals(1, $counters['..100']->getN());
        $this->assertFalse(isset($counters['100.500']));
        $this->assertEquals(2, $counters['500..1000']->getN());
        $this->assertEquals(1, $counters['1000..1700']->getN());
        $this->assertEquals(1, $counters['1700..']->getN());
        $this->assertEquals(5, $counters['0..']->getN());
        $this->assertFalse(isset($counters['..0']));
    }

    /**
     * Test aggregation sort.
     *
     * @param int   $firstId
     * @param array $order
     *
     * @dataProvider dataAggregationsSort
     */
    public function testAggregationsSort(
        int $firstId,
        ? array $order
    ) {
        $query = Query::createMatchAll();
        is_null($order)
            ? $query->aggregateBy('sortable', 'sortable_data', Filter::AT_LEAST_ONE)
            : $query->aggregateBy('sortable', 'sortable_data', Filter::AT_LEAST_ONE, $order);

        $counters = $this
            ->query($query)
            ->getAggregation('sortable')
            ->getCounters();

        $firstCounter = reset($counters);
        $this->assertEquals($firstId, $firstCounter->getId());
    }

    /**
     * data for testAggregationsSort.
     */
    public function dataAggregationsSort()
    {
        return [
            ['3', null],
            ['3', Aggregation::SORT_BY_COUNT_DESC],
            ['1', Aggregation::SORT_BY_COUNT_ASC],
            ['9', Aggregation::SORT_BY_NAME_DESC],
            ['1', Aggregation::SORT_BY_NAME_ASC],
        ];
    }

    /**
     * Test aggregation limit.
     */
    public function testAggregationsLimit()
    {
        $aggregations = $this
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'stores',
                        'stores',
                        Filter::AT_LEAST_ONE,
                        Aggregation::SORT_BY_COUNT_DESC,
                        2
                    )
            )
            ->getAggregations();

        $this->assertCount(2, $aggregations->getAggregation('stores')->getCounters());
    }
}
