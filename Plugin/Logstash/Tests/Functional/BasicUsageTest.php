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

namespace Apisearch\Plugin\Logstash\Tests\Functional;

use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Query\Query;

/**
 * Class BasicUsageTest.
 */
class BasicUsageTest extends LogstashFunctionalTest
{
    /**
     * @var string
     */
    const KEY = 'logstash.apisearch';

    /**
     * Basic usage.
     */
    public function testBasicUsage()
    {
        $redis = static::getStatic('redis.logstash_client_test');
        self::await($redis->del(static::KEY));

        $this->query(Query::createMatchAll());
        $this->assertEquals(
            1,
            static::await($redis->lLen(static::KEY))
        );

        static::await($redis->del(static::KEY));
        $this->deleteIndex();
        $this->createIndex();
        $this->indexTestingItems();
        $this->query(Query::createMatchAll());
        $this->assertEquals(
            4,
            static::await($redis->lLen(static::KEY))
        );

        static::await($redis->lPop(static::KEY));
        static::await($redis->lPop(static::KEY));

        $body = json_decode(static::await($redis->lPop(static::KEY)), true);
        $message = json_decode($body['@message'], true);

        $this->assertEquals(200, $body['@fields']['level']);
        $this->assertEquals('dev', $message['environment']);
        $this->assertEquals(self::$kernel->getUID(), $message['kernel_uid']);
        $this->assertEquals('apisearch', $message['service']);
        $this->assertEquals('26178621test_default', $message['repository_reference']);
        $this->assertEquals('ItemsWereIndexed', $message['type']);
        $body = json_decode(static::await($redis->lPop(static::KEY)), true);
        $message = json_decode($body['@message'], true);
        $this->assertEquals(200, $body['@fields']['level']);
        $this->assertEquals('QueryWasMade', $message['type']);

        try {
            $this->deleteIndex('non-existing', 'non-existing');
        } catch (ResourceNotAvailableException $e) {
            // Ignoring exception
        }

        $body = json_decode(static::await($redis->lPop(static::KEY)), true);
        $this->assertEquals(400, $body['@fields']['level']);
    }
}
