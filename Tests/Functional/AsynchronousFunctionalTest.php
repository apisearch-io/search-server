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

namespace Apisearch\Server\Tests\Functional;

/**
 * Class AsynchronousFunctionalTest.
 */
abstract class AsynchronousFunctionalTest extends ServiceFunctionalTest
{
    /**
     * Time to wait after write command.
     */
    protected static function waitAfterWriteCommand()
    {
        usleep(600000);
    }

    /**
     * Force all connections to be dropped.
     */
    abstract protected function dropConnections();
}
