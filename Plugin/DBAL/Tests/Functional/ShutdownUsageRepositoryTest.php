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

use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetUsage;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;
use DateTime;

/**
 * Class ShutdownUsageRepositoryTest.
 */
class ShutdownUsageRepositoryTest extends ServiceFunctionalTest
{
    use DBALFunctionalTestTrait;

    /**
     * Test shutdown event.
     *
     * @return void
     */
    public function testShutdownEvent(): void
    {
        $usage = $this->getUsageWithoutFlushing();
        $this->assertCount(0, $usage);
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());

        self::usleep(100000);
        $usage = $this->getUsageWithoutFlushing();
        $this->assertCount(0, $usage);

        $this->await(self::$kernel->shutdown());
        self::usleep(100000);

        $usage = $this->getUsageWithoutFlushing();
        $this->assertCount(2, $usage);
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
