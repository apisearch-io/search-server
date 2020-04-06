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

namespace Apisearch\Server\Controller;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\CheckIndex;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CheckIndexController.
 */
class CheckIndexController extends ControllerWithQueryBus
{
    /**
     * Create an index.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $indexUUID = RequestAccessor::getIndexUUIDFromRequest($request);

        return $this
            ->queryBus
            ->ask(new CheckIndex(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $indexUUID
            ))
            ->then(function (bool $alive) {
                return true === $alive
                    ? new Response(null, Response::HTTP_OK)
                    : new Response(null, Response::HTTP_BAD_REQUEST);
            });
    }
}
