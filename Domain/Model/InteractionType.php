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
    const CLICK_RECOMMENDED = 'cli_recomm';
    const CLICK_SUGGESTED = 'cli_sugg';
    const CLICK_SIMILAR = 'cli_sim';
    const BUY = 'buy';
    const FAVOURITE = 'fav';
    const TYPES = [
        self::CLICK,
        self::CLICK_RECOMMENDED,
        self::CLICK_SIMILAR,
        self::CLICK_SUGGESTED,
        self::BUY,
        self::FAVOURITE,
    ];

    const TYPES_ALIAS = [
        'click' => self::CLICK,
        'click_recommended' => self::CLICK_RECOMMENDED,
        'click_similar' => self::CLICK_SIMILAR,
        'click_suggested' => self::CLICK_SUGGESTED,
        'buy' => self::BUY,
        'fav' => self::FAVOURITE,
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

    /**
     * @param string|null $type
     *
     * @return string
     */
    public static function resolveAlias(?string $type)
    {
        return $type ?? self::TYPES_ALIAS[$type] ?? $type;
    }
}
