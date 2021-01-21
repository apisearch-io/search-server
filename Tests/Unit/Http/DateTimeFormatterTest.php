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

namespace Apisearch\Server\Tests\Unit\Http;

use Apisearch\Server\Http\DateTimeFormatter;
use Apisearch\Server\Tests\Unit\BaseUnitTest;
use DateTime;

/**
 * Class DateTimeFormatter.
 */
class DateTimeFormatterTest extends BaseUnitTest
{
    public function testNormalizeRange(): void
    {
        $today = (new DateTime())->setTime(0, 0);
        $todayFormatted = $today->format('Ymd');

        list($from, $to, $days) = DateTimeFormatter::normalizeRange(null, null);
        $this->assertEquals($from, (new DateTime('first day of this month'))->setTime(0, 0));
        $this->assertEquals($to, (new DateTime('first day of next month'))->setTime(0, 0));
        $this->assertEquals($days, \date('t'));

        list($from, $to, $days) = DateTimeFormatter::normalizeRange($todayFormatted, null);
        $this->assertEquals($from, $today);
        $this->assertEquals($to, (new DateTime('first day of next month'))->setTime(0, 0));
        $this->assertEquals($days, $to->diff($from)->days);

        list($from, $to, $days) = DateTimeFormatter::normalizeRange('20210110', '20210201');
        $this->assertEquals($from, (DateTime::createFromFormat('Ymd', '20210110'))->setTime(0, 0));
        $this->assertEquals($to, (DateTime::createFromFormat('Ymd', '20210201'))->setTime(0, 0));
        $this->assertEquals($days, 22);
    }

    public function testFormatDateTime(): void
    {
        $today = (new DateTime())->setTime(0, 0);
        $todayFormatted = $today->format('Ymd');

        $this->assertEquals('', DateTimeFormatter::formatDateTime(null));
        $this->assertEquals($todayFormatted, DateTimeFormatter::formatDateTime($today));
    }
}
