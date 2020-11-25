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

namespace Apisearch\Server\Domain;

use Carbon\Carbon;

/**
 * Class Now.
 */
class Now
{
    /**
     * In epoch time with microseconds.
     *
     * @param Carbon|null $now
     *
     * @return int
     */
    public static function epochTimeWithMicroseconds(Carbon $now = null): int
    {
        $now = $now ?? Carbon::now('UTC');

        return ($now->timestamp * 1000000) + ((int) ($now->micro / 1000) * 1000);
    }
}
