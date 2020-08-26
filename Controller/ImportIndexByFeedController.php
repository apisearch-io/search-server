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
use Apisearch\Server\Domain\Command\ImportIndexByFeed;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportIndexController.
 */
class ImportIndexByFeedController extends ControllerWithCommandBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface<Response>
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $query = $request->query;
        $feed = $query->get('feed');
        $detached = $query->get('detached', false);

        $promise = $this
            ->commandBus
            ->execute(new ImportIndexByFeed(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $feed
            ));

        return $detached
            ? resolve(new Response(202))
            : $promise->then(function () {
                return new Response(200);
            });
    }
}
