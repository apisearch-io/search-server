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

namespace Apisearch\Plugin\Admin\Tests;

use Apisearch\Query\Query;

/**
 * Class GetUsageTest.
 */
class GetUsageTest extends AdminPluginFunctionalTest
{
    /**
     * Test controller result.
     */
    public function testController()
    {
        $this->putToken($this->createTokenByIdAndAppId('token1', static::$appId));
        $this->putToken($this->createTokenByIdAndAppId('token2', static::$appId));
        $this->putToken($this->createTokenByIdAndAppId('token3', static::$anotherAppId));
        $this->putToken($this->createTokenByIdAndAppId('token4', 'yet-another-app'));

        $this->queryNTimes(static::$appId, 10);
        $this->queryNTimes(static::$anotherAppId, 20);
        self::makeCurl('admin_dispatch_imperative_event', [
            'eventName' => 'flush_usage_lines',
        ]);

        $today = \intval((new \DateTime())->format('Ymd'));
        $response = self::makeCurl('admin_get_usage', [], null, [], [
            'from' => $today - 1,
            'to' => $today + 1,
        ]);
        $usage = $response['body'];

        $this->assertEquals([
            'admin' => 10,
            'query' => 30,
        ], $usage);
    }

    /**
     * @param string $appId
     * @param int    $times
     */
    private function queryNTimes(
        string $appId,
        int $times
    ) {
        for ($i = 0; $i < $times; ++$i) {
            $this->query(Query::createMatchAll(), $appId);
        }
    }
}
