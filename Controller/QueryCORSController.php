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

use Apisearch\Http\Http;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetCORSPermissions;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class QueryCORSController.
 */
class QueryCORSController extends ControllerWithQueryBus
{
    /**
     * Request CORS permissions for query
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $headers = $request->headers;
        $origin = $headers->get('Origin', '');

        return $this
            ->queryBus
            ->ask(new GetCORSPermissions(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                $origin
            ))
            ->then(function($origin) {
                return is_null($origin)
                    ? $this->createForbiddenResponse()
                    : $this->createPermittedResponse($origin);
            });
    }

    /**
     * Create permitted response
     *
     * @param string $origin
     *
     * @return Response
     */
    private function createPermittedResponse(string $origin) : Response
    {
        return new Response(null, 204, [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Headers' => implode([
                Http::TOKEN_ID_HEADER
            ]),
            'Access-Control-Allow-Methods' => 'GET'
        ]);
    }

    /**
     * Create forbidden response
     */
    private function createForbiddenResponse()
    {
        return new Response(null, 403);
    }
}
