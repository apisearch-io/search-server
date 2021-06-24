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

use Symfony\Component\HttpFoundation\HeaderBag;

final class RemoteAddr
{
    /**
     * @param HeaderBag $headers
     *
     * @return string
     */
    public static function getRemoteAddrFromHeaderBag(HeaderBag $headers): string
    {
        return $headers->get('HTTP_X_FORWARDED_FOR', $headers->get('REMOTE_ADDR', $headers->get('HTTP_CLIENT_IP', '')));
    }
}
