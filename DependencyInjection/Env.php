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

namespace Apisearch\Server\DependencyInjection;

/**
 * Class Env.
 */
class Env
{
    /**
     * Get environment variable.
     *
     * @param string $variableName
     * @param $defaultValue
     *
     * @return mixed
     */
    public static function get(
        string $variableName,
        $defaultValue
    ) {
        return $_SERVER[$variableName] ?? $defaultValue;
    }
}
