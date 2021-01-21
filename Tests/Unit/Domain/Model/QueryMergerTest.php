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

namespace Apisearch\Server\Tests\Unit\Domain\Plugin;

use Apisearch\Query\Query;
use Apisearch\Server\Domain\Model\QueryMerger;
use PHPUnit\Framework\TestCase;

/**
 * Class QueryMergerTest.
 */
class QueryMergerTest extends TestCase
{
    /**
     * Test pre query merger.
     *
     * @param array  $baseQueryAsArray
     * @param array  $mergeableQueryAsArray
     * @param string $type
     * @param array  $resultQuery
     *
     * @dataProvider dataQueryMerger
     *
     * @return void
     */
    public function testQueryMerger(
        array $baseQueryAsArray,
        array $mergeableQueryAsArray,
        string $type,
        array $resultQuery
    ): void {
        $this->assertEquals(
            $resultQuery,
            QueryMerger::mergeQueries(
                $baseQueryAsArray,
                $mergeableQueryAsArray,
                $type
            )
        );
    }

    /**
     * Get scenarios.
     *
     * @return array
     */
    public function dataQueryMerger(): array
    {
        return [
            [[], [], QueryMerger::BASE, []],
            [[], [], QueryMerger::FORCE, []],
            [[], [], QueryMerger::MERGE, []],

            // Base
            [[], ['q' => 'hello'], QueryMerger::BASE, ['q' => 'hello']],
            [['q' => 'another'], ['q' => 'hello'], QueryMerger::BASE, ['q' => 'another']],
            [['q' => 'another'], [], QueryMerger::BASE, ['q' => 'another']],
            [[], ['filters' => ['b' => ['field' => 'fb']]], QueryMerger::BASE, ['filters' => ['b' => ['field' => 'fb']]]],
            [['filters' => ['a' => ['field' => 'fa']]], ['filters' => ['b' => ['field' => 'fb']]], QueryMerger::BASE, ['filters' => ['a' => ['field' => 'fa']]]],

            // Force
            [[], ['q' => 'hello'], QueryMerger::FORCE, ['q' => 'hello']],
            [['q' => 'another'], ['q' => 'hello'], QueryMerger::FORCE, ['q' => 'hello']],
            [['q' => 'another'], [], QueryMerger::FORCE, ['q' => 'another']],
            [[], ['filters' => ['b' => ['field' => 'fb']]], QueryMerger::FORCE, ['filters' => ['b' => ['field' => 'fb']]]],
            [['filters' => ['a' => ['field' => 'fa']]], ['filters' => ['b' => ['field' => 'fb']]], QueryMerger::FORCE, ['filters' => ['b' => ['field' => 'fb']]]],

            // Merge
            [[], ['q' => 'hello'], QueryMerger::MERGE, ['q' => 'hello']],
            [['q' => 'another'], ['q' => 'hello'], QueryMerger::MERGE, ['q' => 'another']],
            [['q' => 'another'], [], QueryMerger::MERGE, ['q' => 'another']],
            [[], ['filters' => ['b' => ['field' => 'fb']]], QueryMerger::MERGE, ['filters' => ['b' => ['field' => 'fb']]]],
            [['filters' => ['a' => ['field' => 'fa']]], ['filters' => ['b' => ['field' => 'fb']]], QueryMerger::MERGE, ['filters' => [
                'a' => ['field' => 'fa'],
                'b' => ['field' => 'fb'],
            ]]],
            [['q' => 'hello', 'filters' => ['a' => ['field' => 'fa']]], ['filters' => ['b' => ['field' => 'fb']]], QueryMerger::MERGE, ['q' => 'hello', 'filters' => [
                'a' => ['field' => 'fa'],
                'b' => ['field' => 'fb'],
            ]]],
            [['q' => 'hello', 'filters' => ['a' => ['field' => 'fa']]], [], QueryMerger::MERGE, ['q' => 'hello', 'filters' => [
                'a' => ['field' => 'fa'],
            ]]],
        ];
    }
}
