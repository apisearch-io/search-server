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

namespace Apisearch\Server\Tests;

use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Trait PHPUnitModifierTrait.
 */
trait PHPUnitModifierTrait
{
    /**
     * @param array $expected
     * @param array $actual
     *
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    protected function assertArraysEquals(
        array $expected,
        array $actual
    ) {
        $this->assertEquals(
            [],
            \array_diff_key(
                $actual,
                $expected
            )
        );
    }
}
