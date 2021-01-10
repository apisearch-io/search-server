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
    private string $minute;
    private string $hour;
    private string $monthDay;
    private string $month;
    private string $weekDay;
    private string $command;

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
     * @param string $line
     * @param string $command
     *
     * @return self
     */
    public static function fromLine(
        string $line,
        string $command
    ): self {
        list(
            $minute,
            $hour,
            $monthDay,
            $month,
            $weekDay
        ) = \explode(' ', $line, 5);

        return new self(
            $minute,
            $hour,
            $monthDay,
            $month,
            $weekDay,
            $command
        );
    }

    /**
     * @param int    $minutesInterval
     * @param string $command
     */
    public static function fromMinutesInterval(
        int $minutesInterval,
        string $command
    ): self {
        return new self(
            '*/'.$minutesInterval,
            '*',
            '*',
            '*',
            '*',
            $command
        );
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
