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
use Apisearch\Server\Domain\Query\ExportIndex;
use React\Http\Response;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ExportIndexController.
 */
class ExportIndexController extends ControllerWithQueryBus
{
    /**
     * Get tokens.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $indexUUID = RequestAccessor::getIndexUUIDFromRequest($request);
        $format = $request->query->get('format', 'standard');

        return $this
            ->ask(new ExportIndex(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    $indexUUID
                ),
                RequestAccessor::getTokenFromRequest($request),
                $format
            ))
            ->then(function (ReadableStreamInterface $stream) {
                return new Response(200, [
                    'Content-Type' => 'text/plain',
                ], $stream);
            });
    }
}
