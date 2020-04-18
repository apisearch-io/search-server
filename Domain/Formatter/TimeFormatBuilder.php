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

namespace Apisearch\Server\Domain\Formatter;

use DateTime;

/**
 * Class TimeFormatBuilder.
 */
class TimeFormatBuilder
{
    /**
     * Format date from epoch_time with microseconds to elasticsearch
     * basic_date_time.
     *
     * @param int $time
     *
     * @return string
     */
    public function formatTimeFromMillisecondsToBasicDateTime(int $time): string
    {
        $formattedDatetime = (string) ($time / 1000000);
        if (10 === \strlen($formattedDatetime)) {
            $formattedDatetime .= '.';
        }

        $formattedDatetime = \str_pad($formattedDatetime, 17, '0', STR_PAD_RIGHT);
        $datetime = DateTime::createFromFormat('U.u', $formattedDatetime);

        return
            $datetime->format('Ymd\THis').'.'.
            \str_pad(((string) (int) (((int) $datetime->format('u')) / 1000)), 3, '0', STR_PAD_LEFT).
            $datetime->format('P');
    }
}
