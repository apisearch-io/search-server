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

namespace Apisearch\Server\Domain\CommandHandler;

use Apisearch\Server\Domain\Command\ConfigureEnvironment;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ConfigureEnvironmentHandler.
 */
class ConfigureEnvironmentHandler
{
    /**
     * Add token.
     *
     * @param ConfigureEnvironment $configureEnvironment
     *
     * @return PromiseInterface
     */
    public function handle(ConfigureEnvironment $configureEnvironment): PromiseInterface
    {
        return resolve();
    }
}
