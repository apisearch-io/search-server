<?php

/*
 * This file is part of the Search Server Bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @author PuntMig Technologies
 */

declare(strict_types=1);

namespace Puntmig\Search\Server\Tests\Functional\Repository;

use Puntmig\Search\Query\Filter;
use Puntmig\Search\Query\Query;

/**
 * Class AggregationsTest.
 */
trait AggregationsTest
{
    /**
     * Test something.
     */
    public function testSomething()
    {
        $repository = static::$repository;
    }

    /**
     * Test basic aggregations.
     */
    public function testBasicAggregations()
    {
        $repository = static::$repository;
        $aggregations = $repository
            ->query(
                Query::createMatchAll()
                    ->filterBy('color', 'color', ['pink'], FILTER::AT_LEAST_ONE)
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
        $repository = static::$repository;
        $aggregations = $repository->query(
            Query::createMatchAll()
                ->filterBy('nonexistent', 'nonexistent', [])
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
        $repository = static::$repository;
        $aggregations = $repository
            ->query(
                Query::createMatchAll()
                    ->filterBy('color', 'color', ['1'], FILTER::AT_LEAST_ONE)
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
        $repository = static::$repository;
        $aggregations = $repository
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'editorial',
                        'editorial_data',
                        FILTER::AT_LEAST_ONE
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
        $repository = static::$repository;
        $aggregations = $repository
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'stores',
                        'stores',
                        FILTER::AT_LEAST_ONE
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
        $repository = static::$repository;
        $aggregations = $repository
            ->query(
                Query::createMatchAll()
                    ->aggregateBy(
                        'author',
                        'author_data',
                        FILTER::AT_LEAST_ONE
                    )
            )
            ->getAggregations();

        $this->assertCount(3, $aggregations->getAggregation('author')->getCounters());
    }
}
