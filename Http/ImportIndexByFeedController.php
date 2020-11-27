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

namespace Apisearch\Server\Http;

use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\ImportIndexByFeed;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportIndexController.
 */
final class ImportIndexByFeedController extends ControllerWithCommandBus
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
        $detached = \boolval($query->get('detached', false));
        $deleteOldVersions = \boolval($query->get('delete_old_versions', false));
        $currentVersionUUID = \strval($query->get('version', $this->generateUUID4(8)));

        $promise = $this->execute(new ImportIndexByFeed(
            RepositoryReference::create(
                RequestAccessor::getAppUUIDFromRequest($request),
                RequestAccessor::getIndexUUIDFromRequest($request)
            ),
            RequestAccessor::getTokenFromRequest($request),
            $deleteOldVersions,
            $currentVersionUUID,
            $feed
        ));

        return $detached
            ? resolve(new Response(202))
            : $promise->then(function () {
                return new Response(200);
            });
    }
}
