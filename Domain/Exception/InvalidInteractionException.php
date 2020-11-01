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

namespace Apisearch\Server\Domain\Exception;

use Apisearch\Exception\InvalidFormatException;

/**
 * Class InvalidInteractionException.
 */
class InvalidInteractionException extends InvalidFormatException
{
    /**
     * Create exception.
     */
    public static function create(): InvalidInteractionException
    {
        return new self('Invalid click. You should pass a valid user_id or a valid IP instead');
    }
}
