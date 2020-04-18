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

namespace Apisearch\Server\Exception;

use Exception;

/**
 * Class ExternalResourceException.
 */
class ExternalResourceException extends Exception
{
    /**
     * Create external connection exception.
     *
     * @param string $service
     *
     * @return ExternalResourceException
     */
    public static function createExternalConnectionException(string $service): ExternalResourceException
    {
        return new self(\sprintf('A problem with the external connection %s has occurred', $service));
    }
}
