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
use Apisearch\Result\Result;

/**
 * Class FiltersTest.
 */
trait FiltersTest
{
    /**
     * Filter by simple fields.
     *
     * @return void
     */
    public function testFilterBySimpleFields(): void
    {
        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByIds(['1'])),
            ['?1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByIds(['1', '2'])),
            ['?1', '?2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('id', 'id', ['1', '2'])),
            ['?1', '?2', '!3', '!4', '!5']
        );
    }

    /**
     * Filter by metadata fields.
     *
     * @return void
     */
    public function testFilterByDataFields(): void
    {
        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('i', 'field_integer', ['10'], Filter::MUST_ALL)),
            ['?1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('b', 'field_boolean', ['true'], Filter::MUST_ALL)),
            ['?1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('k', 'field_keyword', ['my_keyword'], Filter::MUST_ALL)),
            ['?1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('color', 'color', ['yellow'], Filter::AT_LEAST_ONE)),
            ['!1', '!2', '?3', '!4', '?5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('color', 'color', ['yellow', 'red'], Filter::MUST_ALL)),
            ['!1', '!2', '!3', '!4', '?5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('color', 'color', ['yellow', 'nonexistent'], Filter::MUST_ALL)),
            ['!1', '!2', '!3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('color', 'color', ['nonexistent'], Filter::AT_LEAST_ONE)),
            ['!1', '!2', '!3', '!4', '!5']
        );
    }

    /**
     * Test type filter.
     *
     * @return void
     */
    public function testTypeFilter(): void
    {
        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByTypes(['product'])),
            ['?1', '?2', '!3', '!4', '!5', '!800']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('type', 'type', ['product'])),
            ['?1', '?2', '!3', '!4', '!5', '!800']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByTypes(['product', 'book'])),
            ['?1', '?2', '?3', '!4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterByTypes(['book'])),
            ['!1', '!2', '?3', '!4', '!5']
        );

        $this->assertEmpty(
            $this->query(Query::createMatchAll()->filterByTypes(['_nonexistent']))->getItems()
        );

        $this->assertEmpty(
            $this->query(
                Query::createMatchAll()->filterByTypes(['product']),
                self::$anotherAppId
            )->getItems()
        );
    }

    /**
     * Build created at filtered Result.
     *
     * @param string $filter
     *
     * @return Result
     */
    private function buildCreatedAtFilteredResult(string $filter): Result
    {
        return $this->query(Query::createMatchAll()->filterByDateRange('created_at', 'created_at', [], [$filter], Filter::AT_LEAST_ONE, false));
    }

    /**
     * Build created at filtered Result.
     *
     * @param string $filter
     *
     * @return Result
     */
    private function buildCreatedAtUniverseFilteredResult(string $filter): Result
    {
        return $this->query(Query::createMatchAll()->filterUniverseByDateRange('created_at', [$filter], Filter::AT_LEAST_ONE));
    }

    /**
     * Filter by strange character.
     *
     * @return void
     */
    public function testFilterByStrangeCharacter(): void
    {
        $this->assertCount(
            1,
            $this->query(Query::createMatchAll()->filterBy('char', 'strange_field', ['煮']))->getItems()
        );
    }

    /**
     * Test exclude filter.
     *
     * @return void
     */
    public function testExcludeFilter(): void
    {
        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('color', 'color', ['yellow'], Filter::EXCLUDE, false)),
            ['?1', '?2', '!3', '?4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('color', 'color', ['yellow', 'pink'], Filter::EXCLUDE, false)),
            ['!1', '?2', '!3', '?4', '!5']
        );

        $this->assertResults(
            $this->query(Query::createMatchAll()->filterBy('color', 'color', [], Filter::EXCLUDE, false)),
            ['?1', '?2', '?3', '?4', '?5']
        );
    }

    /**
     * Test filter by uuid field
     *
     * @group lol
     */
    public function testByUUIDField()
    {
        $this->assertResults(
            $this->query(Query::createFromArray([
                'filters' => [[
                    'name' => 'x',
                    'field' => 'uuid',
                    'values' => ['4~bike']
                ]]
            ])),
            ['4', '!2', '!3', '!1', '!5']
        );

        $this->assertResults(
            $this->query(Query::createFromArray([
                'filters' => [[
                    'name' => 'x',
                    'field' => 'indexed_metadata.uuid',
                    'values' => ['4~bike']
                ]]
            ])),
            ['4', '!2', '!3', '!1', '!5']
        );
    }
}
