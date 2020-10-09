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
use Apisearch\Server\Domain\ImperativeEvent\FlushInteractions;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\ImperativeEvent\FlushUsageLines;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class HealthTest.
 */
class HealthTest extends ServiceFunctionalTest
{
    use DBALFunctionalTestTrait;

    /**
     * Test if health check has redis.
     */
    public function testCheckHealth()
    {
        $this->click('123', 'product~1', 1, Origin::createEmpty());
        $this->click('123', 'product~1', 1, Origin::createEmpty());
        $this->click('456', 'product~1', 1, Origin::createEmpty());
        $this->query(Query::create('hola'));
        $this->query(Query::createMatchAll());
        $this->query(Query::createMatchAll());
        $this->dispatchImperative(new FlushInteractions());
        $this->dispatchImperative(new FlushUsageLines());
        $this->dispatchImperative(new FlushSearches());

        $response = $this->checkHealth();
        $this->assertTrue($response['status']['dbal']);
        $this->assertEquals([
            'interactions' => 3,
            'usage_lines' => 6,
            'search_lines' => 1
        ], $response['info']['dbal']);

        $this->click('555', 'product~1', 1, Origin::createEmpty());
        $this->query(Query::create('engonga'));
        $this->query(Query::create('lol'));
        $this->query(Query::createMatchAll());
        $this->dispatchImperative(new FlushInteractions());
        $this->dispatchImperative(new FlushUsageLines());
        $this->dispatchImperative(new FlushSearches());

        $response = $this->checkHealth();
        $this->assertTrue($response['status']['dbal']);
        $this->assertEquals([
            'interactions' => 4,
            'usage_lines' => 7,
            'search_lines' => 3
        ], $response['info']['dbal']);
    }
}
