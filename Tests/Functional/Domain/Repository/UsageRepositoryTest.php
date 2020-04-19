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
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class UsageRepositoryTest.
 */
class UsageRepositoryTest extends ServiceFunctionalTest
{
    /**
     * Test simple.
     */
    public function testSimpleUsage()
    {
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $usage = $this->getUsage();

        $this->assertEquals([
            'indexwascreated' => 2,
            'tokensweredeleted' => 1,
            'itemswereindexed' => 1,
            'itemsN' => 5,
            'querywasmade' => 3,
        ], $usage);

        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $usage = $this->getUsage();

        $this->assertEquals([
            'indexwascreated' => 2,
            'tokensweredeleted' => 1,
            'itemswereindexed' => 1,
            'itemsN' => 5,
            'querywasmade' => 7,
        ], $usage);

        static::indexTestingItems();
        $usage = $this->getUsage();

        $this->assertEquals([
            'indexwascreated' => 2,
            'tokensweredeleted' => 1,
            'itemswereindexed' => 2,
            'itemsN' => 10,
            'querywasmade' => 7,
        ], $usage);
    }
}
