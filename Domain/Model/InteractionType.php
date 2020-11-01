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
 * Class Interaction type.
 */
class InteractionType
{
    const CLICK = 'cli';
    const BUY = 'buy';
    const FAVOURITE = 'fav';
    const TYPES = [
        self::CLICK,
        self::BUY,
        self::FAVOURITE,
    ];

    /**
     * @param string $interactionType
     *
     * @return bool
     */
    public static function isValid(string $interactionType): bool
    {
        return \in_array($interactionType, self::TYPES);
    }
}
