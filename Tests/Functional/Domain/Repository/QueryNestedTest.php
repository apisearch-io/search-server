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
 * Class QueryNestedTest.
 */
trait QueryNestedTest
{
    /**
     * Test nested values filtering field.
     */
    public function testedNestedFieldFiltering()
    {
        $items = $this
            ->query(Query::createMatchAll()->filterBy('xxx', 'brand.id', [1]))
            ->getItems();

        $this->assertCount(
            3,
            $items
        );

        $items = $this
            ->query(Query::createMatchAll()->filterBy('xxx', 'brand.category', [1, 2]))
            ->getItems();

        $this->assertCount(
            5,
            $items
        );
    }

    /**
     * Test nested values filtering universe field.
     */
    public function testedNestedUniverseFieldFiltering()
    {
        $items = $this
            ->query(Query::createMatchAll()->filterUniverseBy('brand.id', [1]))
            ->getItems();

        $this->assertCount(
            3,
            $items
        );

        $items = $this
            ->query(Query::createMatchAll()->filterUniverseBy('brand.category', [1, 2]))
            ->getItems();

        $this->assertCount(
            5,
            $items
        );
    }
}
