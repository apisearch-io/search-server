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
use Apisearch\Server\Domain\Command\ImportIndexByStream;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use React\Stream\ReadableStreamInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportIndexByStreamController.
 */
final class ImportIndexByStreamController extends ControllerWithCommandBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface<Response>
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $query = $request->query;
        $stream = $request->get('body');
        $detached = $query->get('detached', false);

        if (!$stream instanceof ReadableStreamInterface) {
            return resolve(new Response(400));
        }

        $promise = $this->execute(new ImportIndexByStream(
            RepositoryReference::create(
                RequestAccessor::getAppUUIDFromRequest($request),
                RequestAccessor::getIndexUUIDFromRequest($request)
            ),
            RequestAccessor::getTokenFromRequest($request),
            $stream
        ));

        return $detached
            ? resolve(new Response(202))
            : $promise->then(function () {
                return new Response(200);
            });
    }
}
