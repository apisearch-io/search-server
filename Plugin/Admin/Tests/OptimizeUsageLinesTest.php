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
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;
use DateTime;

/**
 * Class OptimizeUsageLinesTest.
 */
class OptimizeUsageLinesTest extends HttpFunctionalTest
{
    use AdminPluginFunctionalTest;

    /**
     * Test controller result.
     *
     * @return void
     */
    public function testController(): void
    {
        $this->putToken($this->createTokenByIdAndAppId('token1', static::$appId));
        $this->putToken($this->createTokenByIdAndAppId('token2', static::$appId));
        $this->putToken($this->createTokenByIdAndAppId('token3', static::$anotherAppId));
        $this->putToken($this->createTokenByIdAndAppId('token4', 'yet-another-app'));

        $this->queryNTimes(static::$appId, 10);
        $this->queryNTimes(static::$anotherAppId, 20);
        $this->request('admin_optimize_usage_lines', [], null, [], [
            'from' => (new DateTime())->modify('-1 day')->format('Ymd'),
            'to' => (new DateTime())->modify('+1 day')->format('Ymd'),
        ]);

        $yesterday = \intval((new \DateTime())->modify('yesterday')->format('Ymd'));
        $tomorrow = \intval((new \DateTime())->modify('tomorrow')->format('Ymd'));
        $response = $this->request('admin_get_usage', [], null, [], [
            'from' => $yesterday,
            'to' => $tomorrow,
        ]);
        $usage = $response['body'];

        $this->assertEquals([
            'data' => [
                'admin' => 10,
                'query' => 30,
            ],
            'days' => 2,
            'from' => \strval($yesterday),
            'to' => \strval($tomorrow),
        ], $usage);
    }

    /**
     * Test bad requests.
     *
     * @param mixed $from
     * @param mixed $to
     *
     * @dataProvider dataBadRequests
     *
     * @return void
     */
    public function testBadRequests($from, $to): void
    {
        $this->expectException(InvalidFormatException::class);
        $this->request('admin_optimize_usage_lines', [], null, [], [
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Data bad dates.
     *
     * @return array
     */
    public function dataBadRequests(): array
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
     *
     * @return void
     */
    private function queryNTimes(
        string $appId,
        int $times
    ): void {
        for ($i = 0; $i < $times; ++$i) {
            $this->query(Query::createMatchAll(), $appId);
        }
    }
}
