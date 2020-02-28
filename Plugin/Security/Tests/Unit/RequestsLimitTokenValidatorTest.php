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

namespace Apisearch\Plugin\Security\Tests\Unit;

use Apisearch\Plugin\Security\Domain\Token\RequestsLimitTokenValidator;
use Clue\React\Redis\Client;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestsLimitTokenValidatorTest.
 */
class RequestsLimitTokenValidatorTest extends TestCase
{
    /**
     * Test format.
     *
     * @dataProvider dataFormat
     */
    public function testFormat(
        string $data,
        DateTime $now,
        array $result
    ) {
        $redisWrapper = $this->prophesize(Client::class);
        $validator = new RequestsLimitTokenValidator($redisWrapper->reveal());
        $this->assertEquals(
            $result,
            $validator->getHitsAndTimePositionByData(
                $data,
                $now
            )
        );
    }

    /**
     * Data format.
     */
    public function dataFormat()
    {
        $now = new DateTime('2019-03-11 12:34:57');
        $secondsMissingYear = 25529104;

        return [
            ['10/s', $now, [10, $now->format('Y-m-d\TH:i:s'), 2]],
            ['10/i', $now, [10, $now->format('Y-m-d\TH:i'), 4]],
            ['10/h', $now, [10, $now->format('Y-m-d\TH'), 1504]],
            ['10/d', $now, [10, $now->format('Y-m-d'), 41104]],
            ['10/y', $now, [10, $now->format('Y'), $secondsMissingYear]],
            ['10K/y', $now, [10000, $now->format('Y'), $secondsMissingYear]],
            ['10M/y', $now, [10000000, $now->format('Y'), $secondsMissingYear]],
            ['10MM/y', $now, [10000000000, $now->format('Y'), $secondsMissingYear]],
            ['10', $now, [10, '', 0]],

            /*
             * Bad formats
             */
            ['hh', $now, []],
            ['/', $now, []],
            ['/y', $now, []],
            ['', $now, []],
        ];
    }
}
