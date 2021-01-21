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
use Apisearch\Server\Domain\ImperativeEvent\FlushInteractions;
use Apisearch\Server\Domain\ImperativeEvent\FlushSearches;
use Apisearch\Server\Domain\ImperativeEvent\FlushUsageLines;
use Apisearch\Server\Domain\Model\Origin;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class HealthCheckMiddlewareTest.
 */
class HealthCheckMiddlewareTest extends ServiceFunctionalTest
{
    use DBALFunctionalTestTrait;

    /**
     * Test if health check has redis.
     *
     * @return void
     */
    public function testCheckHealth(): void
    {
        $this->click('123', 'product~1', 1, null, Origin::createEmpty());
        $this->click('123', 'product~1', 1, null, Origin::createEmpty());
        $this->click('456', 'product~1', 1, null, Origin::createEmpty());
        $this->query(Query::create('hola')->byUser(new User('1')));
        $this->query(Query::createMatchAll()->byUser(new User('1')));
        $this->query(Query::createMatchAll()->byUser(new User('1')));
        $this->dispatchImperative(new FlushInteractions());
        $this->dispatchImperative(new FlushUsageLines());
        $this->dispatchImperative(new FlushSearches());

        $response = $this->checkHealth();
        $this->assertTrue($response['status']['dbal']);
        $this->assertGreaterThan(0, $response['info']['dbal']['ping_in_microseconds']);
        unset($response['info']['dbal']['ping_in_microseconds']);
        $this->assertEquals([
            'interactions' => 3,
            'usage_lines' => 6,
            'search_lines' => 1,
            'tokens' => 0,
            'logs' => 6,
        ], $response['info']['dbal']);

        $this->click('555', 'product~1', 1, null, Origin::createEmpty());
        $this->query(Query::create('engonga')->byUser(new User('1')));
        $this->query(Query::create('lol')->byUser(new User('1')));
        $this->query(Query::createMatchAll()->byUser(new User('1')));
        $this->putToken($this->createTokenByIdAndAppId('lala'));
        $this->dispatchImperative(new FlushInteractions());
        $this->dispatchImperative(new FlushUsageLines());
        $this->dispatchImperative(new FlushSearches());

        $response = $this->checkHealth();
        $this->assertTrue($response['status']['dbal']);
        $this->assertGreaterThan(0, $response['info']['dbal']['ping_in_microseconds']);
        unset($response['info']['dbal']['ping_in_microseconds']);
        $this->assertEquals([
            'interactions' => 4,
            'usage_lines' => 8,
            'search_lines' => 3,
            'tokens' => 1,
            'logs' => 6,
        ], $response['info']['dbal']);
    }
}
