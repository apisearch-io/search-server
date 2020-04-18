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

namespace Apisearch\Server\Domain\Model;

/**
 * Class CrontabLine.
 */
class CrontabLine
{
    /**
     * @var string
     */
    private $minute;

    /**
     * @var string
     */
    private $hour;

    /**
     * @var string
     */
    private $monthDay;

    /**
     * @var string
     */
    private $month;

    /**
     * @var string
     */
    private $weekDay;

    /**
     * @var string
     */
    private $command;

    /**
     * CrontabLine constructor.
     *
     * @param string $minute
     * @param string $hour
     * @param string $monthDay
     * @param string $month
     * @param string $weekDay
     * @param string $command
     */
    public function __construct(
        string $minute,
        string $hour,
        string $monthDay,
        string $month,
        string $weekDay,
        string $command)
    {
        $this->minute = $minute;
        $this->hour = $hour;
        $this->monthDay = $monthDay;
        $this->month = $month;
        $this->weekDay = $weekDay;
        $this->command = $command;
    }

    /**
     * To string.
     *
     * @param string $rootPath
     *
     * @return string
     */
    public function toString(string $rootPath): string
    {
        return \sprintf('%s %s %s %s %s %s',
            $this->minute,
            $this->hour,
            $this->monthDay,
            $this->month,
            $this->weekDay,
            "cd $rootPath && ".$this->command
        );
    }
}
