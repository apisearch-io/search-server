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

namespace Apisearch\Server\Http;

use DateTime;
use DateTimeZone;

/**
 * Class DateTimeFormatter.
 */
class DateTimeFormatter
{
    /**
     * @param string|null $from
     * @param string|null $to
     *
     * @return [DateTime, DateTime, int]
     */
    public static function normalizeRange(
        ?string $from,
        ?string $to
    ): array {
        $from = $from
            ? DateTime::createFromFormat('Ymd', \strval($from), new DateTimeZone('UTC'))
            : (new DateTime('first day of this month', new DateTimeZone('UTC')));

        $to = $to
            ? DateTime::createFromFormat('Ymd', \strval($to), new DateTimeZone('UTC'))
            : (new DateTime('first day of next month', new DateTimeZone('UTC')));

        return [
            $from->setTime(0, 0),
            $to->setTime(0, 0),
            $to->diff($from)->days,
        ];
    }

    /**
     * @param DateTime|null $dateTime
     *
     * @return string
     */
    public static function formatDateTime(?DateTime $dateTime): string
    {
        if (\is_null($dateTime)) {
            return '';
        }

        return $dateTime->format('Ymd');
    }
}
