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

namespace Apisearch\Server\Domain\QueryHandler;

use Apisearch\Server\Domain\Query\GetCORSPermissions;
use Apisearch\Server\Domain\WithRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class GetQueryCORSPermissionsHandler.
 */
class GetCORSPermissionsHandler extends WithRepositoryAndEventPublisher
{
    /**
     * Get CORS permission
     *
     * @param GetCORSPermissions $getCORSPermissions
     *
     * @return PromiseInterface<bool>
     */
    public function handle(GetCORSPermissions $getCORSPermissions): PromiseInterface
    {
        return resolve('*');
    }
}
