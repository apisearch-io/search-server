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
use DateTime;

/**
 * Trait UsageRepositoryTest.
 */
trait UsageRepositoryTest
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
            'query' => 3,
            'admin' => 4,
        ], $usage);

        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $usage = $this->getUsage();

        $this->assertEquals([
            'query' => 7,
            'admin' => 4,
        ], $usage);

        static::indexTestingItems();
        $usage = $this->getUsage();

        $this->assertEquals([
            'query' => 7,
            'admin' => 5,
        ], $usage);

        static::indexTestingItems(static::$appId, static::$anotherIndex);
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll(), static::$appId, static::$anotherIndex);

        $usage = $this->getUsage();
        $this->assertEquals([
            'query' => 9,
            'admin' => 6,
        ], $usage);

        $usage = $this->getUsage(static::$appId);
        $this->assertEquals([
            'query' => 9,
            'admin' => 6,
        ], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index);
        $this->assertEquals([
            'query' => 8,
            'admin' => 3,
        ], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$anotherIndex);
        $this->assertEquals([
            'query' => 1,
            'admin' => 2,
        ], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index, new DateTime('+ 1 minute'));
        $this->assertEquals([], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index, new DateTime('-1 day'));
        $this->assertEquals([
            'query' => 8,
            'admin' => 3,
        ], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index, new DateTime('-2 day'), new DateTime('-1 day'));
        $this->assertEquals([], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index, new DateTime('-1 day'), new DateTime('+1 day'));
        $this->assertEquals([
            'query' => 8,
            'admin' => 3,
        ], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index, new DateTime('-1 day'), new DateTime('+1 day'), 'query');
        $this->assertEquals([
            'query' => 8,
        ], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index, new DateTime('-1 day'), new DateTime('+1 day'), 'admin');
        $this->assertEquals([
            'admin' => 3,
        ], $usage);

        $usage = $this->getUsage(static::$appId, null, static::$index, new DateTime('-1 day'), new DateTime('+1 day'), null, true);
        $this->assertEquals([
            (new DateTime())->setTime(0, 0, 0)->getTimestamp() => [
                'query' => 8,
                'admin' => 3,
                ],
        ], $usage);
    }
}
