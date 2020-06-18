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

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Query\Query;
use DateTime;

/**
 * Class OptimizeUsageLinesTest.
 */
class OptimizeUsageLinesTest extends AdminPluginFunctionalTest
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
        self::makeCurl('admin_optimize_usage_lines', [], null, [], [
            'from' => (new DateTime())->modify('-1 day')->format('Ymd'),
            'to' => (new DateTime())->modify('+1 day')->format('Ymd'),
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
     * Test bad requests.
     *
     * @param mixed $from
     * @param mixed $to
     *
     * @dataProvider dataBadRequests
     */
    public function testBadRequests($from, $to)
    {
        $this->expectException(InvalidFormatException::class);
        self::makeCurl('admin_optimize_usage_lines', [], null, [], [
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Data bad dates.
     */
    public function dataBadRequests()
    {
        return [
            [
                (new DateTime())->modify('-1 day')->format('Y'),
                (new DateTime())->modify('+1 day')->format('Ymd'),
            ],
            [
                (new DateTime())->modify('-1 day')->format('Ymd'),
                (new DateTime())->modify('+1 day')->format('Y'),
            ],
            [
                (new DateTime())->modify('-1 day')->format('Ymd'),
                null,
            ],
            [
                null,
                (new DateTime())->modify('+1 day')->format('Ymd'),
            ],
            [
                null,
                null,
            ],
            [
                false,
                (new DateTime())->modify('+1 day')->format('Ymd'),
            ],
            [
                123,
                (new DateTime())->modify('+1 day')->format('Ymd'),
            ],
        ];
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
