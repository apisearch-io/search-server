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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class Env.
 */
class Env
{
    /**
     * @param string $variableName
     * @param $defaultValue
     * @param bool $throwExceptionOnMissing
     *
     * @return mixed
     */
    public static function get(
        string $variableName,
        $defaultValue,
        bool $throwExceptionOnMissing = false
    ) {
        $value = $_ENV[$variableName] ?? $_SERVER[$variableName] ?? $defaultValue;

        if ($throwExceptionOnMissing && empty($value)) {
            throw new InvalidConfigurationException(
                "Missing configuration value $variableName. Check that the environment variable exists or that the required value on configuration is properly set."
            );
        }

        return $value;
    }

    /**
     * @param string $variableName
     * @param $defaultValue
     * @param bool $throwExceptionOnMissing
     *
     * @return string[]
     */
    public static function getArray(
        string $variableName,
        $defaultValue,
        bool $throwExceptionOnMissing = false
    ): array {
        $value = static::get($variableName, $defaultValue, $throwExceptionOnMissing);

        if (\is_string($value)) {
            $value = \explode(',', $value);
            $value = \array_map('trim', $value);
        }

        return $value;
    }
}
