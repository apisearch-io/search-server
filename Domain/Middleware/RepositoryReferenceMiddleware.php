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

namespace Apisearch\Server\Domain\Middleware;

use Apisearch\Exception\ForbiddenException;
use Apisearch\Server\Domain\AppRequiredCommand;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;

/**
 * Class RepositoryReferenceMiddleware.
 */
final class RepositoryReferenceMiddleware
{
    /**
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        $hasRepositoryReference = ($command instanceof CommandWithRepositoryReferenceAndToken);
        if ($hasRepositoryReference) {
            if (
                ($command instanceof AppRequiredCommand) &&
                empty($command->getAppUUID())
            ) {
                throw ForbiddenException::createAppIdIsRequiredException();
            }

            if (
                ($command instanceof IndexRequiredCommand) &&
                empty($command->getIndexUUID())
            ) {
                throw ForbiddenException::createIndexIsRequiredException();
            }
        }

        return $next($command);
    }
}
