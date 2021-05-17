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
use Apisearch\Query\ScoreStrategies;
use Apisearch\Query\ScoreStrategy;
use Apisearch\Query\SortBy;

/**
 * Class ScoreStrategyTest.
 */
trait ScoreStrategyTest
{
    /**
     * Test default strategy.
     *
     * @return void
     */
    public function testDefaultStrategy(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createDefault())
                )
        );

        $this->assertResults(
            $result,
            ['1', '2', '3', '4', '5']
        );
    }

    /**
     * Test relevance strategy.
     *
     * @return void
     */
    public function testRelevanceStrategyFieldValue(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createFieldBoosting(
                            'relevance'
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '{1', '4}', '3', '2']
        );
    }

    /**
     * Test custom function strategy.
     *
     * @return void
     */
    public function testCustomFunctionStrategy(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createCustomFunction(
                            'doc["indexed_metadata.price"].value',
                            1.0
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['3', '2', '1', '4', '5']
        );

        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty(ScoreStrategies::MULTIPLY)
                        ->addScoreStrategy(ScoreStrategy::createWeightFunction(
                            2,
                            Filter::create('id', ['4'], Filter::MUST_ALL, Filter::TYPE_FIELD)
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['4', '{2', '1', '4', '5}']
        );
    }

    /**
     * Score strategy composed with nested filter and sorting.
     *
     * @return void
     */
    public function testScoreStrategyWithNested(): void
    {
        $this->markTestIncomplete('Should be tested deeper with complex fields');
        $result = $this->query(
            Query::createMatchAll()
                ->filterBy('brand', 'brand_id', [1, 2, 3, 4])
                ->sortBy(
                    SortBy::create()
                        ->byValue(SortBy::SCORE)
                        ->byNestedField('brand_id', 'ASC')
                )
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createCustomFunction(
                            'doc["indexed_metadata.simple_int"].value',
                            1.0
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['4', '1', '2', '3', '5']
        );
    }

    /**
     * Test decay.
     *
     * @return void
     */
    public function testScoreStrategyDecay(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createDecayFunction(
                            ScoreStrategy::DECAY_GAUSS,
                            'relevance',
                            '0',
                            '45',
                            '10',
                            0.5
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['2', '3', '1', '4', '5']
        );

        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createDecayFunction(
                            ScoreStrategy::DECAY_GAUSS,
                            'relevance',
                            '110',
                            '50',
                            '10',
                            0.5
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '{4', '1}', '3', '2']
        );
    }

    /**
     * Test several score strategies.
     *
     * @return void
     */
    public function testSeveralScoreStrategies(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createFieldBoosting(
                            'relevance',
                            1.0,
                            1.0,
                            ScoreStrategy::MODIFIER_LN,
                            1.0
                        ))
                        ->addScoreStrategy(ScoreStrategy::createCustomFunction(
                            'doc["indexed_metadata.simple_int"].value',
                            1.0
                        ))
                        ->addScoreStrategy(ScoreStrategy::createDecayFunction(
                            ScoreStrategy::DECAY_GAUSS,
                            'relevance',
                            '110',
                            '50',
                            '10',
                            0.5,
                            50,
                            Filter::create('price', [2000], Filter::MUST_ALL, Filter::TYPE_FIELD)
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['3', '4', '1', '2', '5']
        );
    }

    /**
     * Test score strategy for array inside indexed_metadata.
     *
     * @return void
     */
    public function testScoreStrategyInsideSimpleArray(): void
    {
        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createFieldBoosting(
                            'array_of_values.first'
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['3', '1', '{4', '2', '5}']
        );

        $result = $this->query(
            Query::create('Código de Hernando')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createFieldBoosting(
                            'array_of_values.first'
                        ))
                )
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals(3, $result->getFirstItem()->getId());
    }

    /**
     * Test score strategy for array inside indexed_metadata.
     *
     * @return void
     */
    public function testScoreStrategyInsideArrayOfArrays(): void
    {
        $this->markTestIncomplete('Should be tested deeper with complex fields');
        $result = $this->query(
            Query::createMatchAll()
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createFieldBoosting(
                            'brand.rank',
                            ScoreStrategy::DEFAULT_FACTOR,
                            0.0,
                            ScoreStrategy::MODIFIER_NONE,
                            ScoreStrategy::DEFAULT_WEIGHT,
                            Filter::create(
                                'brand.id',
                                [1],
                                Filter::MUST_ALL,
                                Filter::TYPE_FIELD
                            ),
                            ScoreStrategy::SCORE_MODE_MAX
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['4', '1', '5', '{2', '3}']
        );
    }

    /**
     * Test several score strategies.
     *
     * @return void
     */
    public function testSeveralScoreStrategiesTypeSUM(): void
    {
        $result = $this->query(
            Query::create('c')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty(ScoreStrategies::SUM)
                        ->addScoreStrategy(ScoreStrategy::createFieldBoosting(
                            'relevance',
                            1.0,
                            1.0,
                            ScoreStrategy::MODIFIER_LN,
                            1.0
                        ))
                        ->addScoreStrategy(ScoreStrategy::createCustomFunction(
                            'doc["indexed_metadata.simple_int"].value',
                            1.0
                        ))
                        ->addScoreStrategy(ScoreStrategy::createDecayFunction(
                            ScoreStrategy::DECAY_GAUSS,
                            'relevance',
                            '110',
                            '50',
                            '10',
                            0.5,
                            50,
                            Filter::create('price', [2000], Filter::MUST_ALL, Filter::TYPE_FIELD)
                        ))
                )
        );

        $firstResultScore = $result
            ->getFirstItem()
            ->getScore();

        $this->assertTrue(
            23 > $firstResultScore &&
            $firstResultScore > 22
        );

        $result = $this->query(
            Query::create('c')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty(ScoreStrategies::MULTIPLY)
                        ->addScoreStrategy(ScoreStrategy::createFieldBoosting(
                            'relevance',
                            1.0,
                            1.0,
                            ScoreStrategy::MODIFIER_LN,
                            1.0
                        ))
                        ->addScoreStrategy(ScoreStrategy::createCustomFunction(
                            'doc["indexed_metadata.simple_int"].value',
                            1.0
                        ))
                        ->addScoreStrategy(ScoreStrategy::createDecayFunction(
                            ScoreStrategy::DECAY_GAUSS,
                            'relevance',
                            '110',
                            '50',
                            '10',
                            0.5,
                            50,
                            Filter::create('price', [2000], Filter::MUST_ALL, Filter::TYPE_FIELD)
                        ))
                )
        );

        $firstResultScore = $result
            ->getFirstItem()
            ->getScore();

        $this->assertTrue(
            291 > $firstResultScore &&
            $firstResultScore > 290
        );
    }

    /**
     * Testing score strategies to boost external filters, outside from the
     * query scope, but inside the filtered universe.
     *
     * @return void
     */
    public function testNonQueryScoreStrategies(): void
    {
        $result = $this->query(
            Query::create('Hernando')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightFunction(
                            2,
                            Filter::create('stories', ['3'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                            true
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['3', '!5', '!1', '!4', '!2']
        );

        $result = $this->query(
            Query::create('Hernando boosting')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightFunction(
                            2,
                            Filter::create('stores', ['three'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                            true
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '{3', '1', '2}', '!4']
        );

        $result = $this->query(
            Query::create('Hernando')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightFunction(
                            2,
                            Filter::create('stores', ['three'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                            false
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '3', '!1', '!2', '!4']
        );

        $result = $this->query(
            Query::create('barcelona')
                ->filterBy('color', 'color', ['red'])
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightFunction(
                            1,
                            Filter::create('relevance', ['40..60'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                            false
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '{1', '4}', '!2', '!3']
        );

        $result = $this->query(
            Query::create('barcelona')
                ->filterUniverseBy('color', ['red'])
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightFunction(
                            1,
                            Filter::create('relevance', ['40..60'], Filter::MUST_ALL, Filter::TYPE_RANGE),
                            false
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '!1', '!4', '!2', '!3']
        );
    }

    public function testMultiFilterInWeightFilterFunction()
    {
        $result = $this->query(
            Query::create('coloryellow')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightMultiFilterFunction(
                            20,
                            [
                                Filter::create('color', ['blue'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                                Filter::create('color', ['yellow'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                            ],
                            true
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['3', '5', '!1', '!2', '!4']
        );

        $result = $this->query(
            Query::create('coloryellow')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightMultiFilterFunction(
                            20,
                            [
                                Filter::create('color', ['red'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                                Filter::create('color', ['yellow'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                            ],
                            true
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '3', '!1', '!2', '!4']
        );

        $result = $this->query(
            Query::create('colorred')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightMultiFilterFunction(
                            20,
                            [
                                Filter::create('color', ['red'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                                Filter::create('color', ['yellow'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                            ],
                            false
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['5', '!3', '!1', '!2', '!4']
        );

        $result = $this->query(
            Query::create('colorred')
                ->setScoreStrategies(
                    ScoreStrategies::createEmpty()
                        ->addScoreStrategy(ScoreStrategy::createWeightMultiFilterFunction(
                            20,
                            [
                                Filter::create('color', ['blue'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                                Filter::create('color', ['yellow'], Filter::MUST_ALL, Filter::TYPE_FIELD),
                            ],
                            false
                        ))
                )
        );

        $this->assertResults(
            $result,
            ['3', '5', '!1', '!2', '!4']
        );
    }
}
