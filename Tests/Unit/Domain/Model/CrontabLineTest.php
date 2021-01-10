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

namespace Apisearch\Server\Tests\Unit\Domain\Plugin;

use Apisearch\Server\Domain\Model\CrontabLine;
use PHPUnit\Framework\TestCase;

/**
 * Class CrontabLineTest.
 */
class CrontabLineTest extends TestCase
{
    /**
     * Test crontab line creation.
     */
    public function testCrontabLineCreation()
    {
        $crontabLine = new CrontabLine('1', '2', '*', '4', '10', 'command');
        $this->assertEquals('1 2 * 4 10 cd /var/www && command', $crontabLine->toString('/var/www'));
    }

    /**
     * Test create from line.
     */
    public function testCreateFromLine()
    {
        $crontabLine = CrontabLine::fromLine('1 2 3 * *', 'command');
        $this->assertEquals('1 2 3 * * cd /var/www && command', $crontabLine->toString('/var/www'));
    }

    /**
     * Test create from minutes interval.
     */
    public function testCreateFromMinutesInterval()
    {
        $crontabLine = CrontabLine::fromMinutesInterval(35, 'command');
        $this->assertEquals('*/35 * * * * cd /var/www && command', $crontabLine->toString('/var/www'));

        $crontabLine = CrontabLine::fromMinutesInterval(140, 'command');
        $this->assertEquals('*/140 * * * * cd /var/www && command', $crontabLine->toString('/var/www'));
    }
}
