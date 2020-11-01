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

namespace Apisearch\Plugin\DBAL\Tests\Functional;

use Apisearch\Model\User;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Domain\Query\GetUsage;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;
use DateTime;

/**
 * Class ShutdownSearchesRepositoryTest.
 */
class ShutdownSearchesRepositoryTest extends ServiceFunctionalTest
{
    use DBALFunctionalTestTrait;

    /**
     * Test shutdown event.
     */
    public function testShutdownEvent()
    {
        $searches = $this->getSearches(false);
        $this->assertEquals(0, $searches);
        $this->query(Query::create('Code da vinci')->byUser(new User('u1')), null, null, null, [], new Origin('', '', Origin::TABLET));
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());
        $this->query(Query::create('Matutano'), null, null, null, [], Origin::createEmpty());

        self::usleep(100000);
        $searches = $this->getSearches(false);
        $this->assertEquals(0, $searches);

        $this->await(self::$kernel->shutdown());
        self::usleep(100000);

        $searches = $this->getSearches(false);
        $this->assertEquals(1, $searches);
    }

    /**
     * Get usages without flushing before.
     *
     * @return array
     */
    public function getUsageWithoutFlushing(): array
    {
        return self::askQuery(new GetUsage(
            RepositoryReference::createFromComposed(static::$appId.'_'.static::$index),
            static::getGodToken(),
            new DateTime('first day of this month'),
            null, null, false
        ));
    }
}
