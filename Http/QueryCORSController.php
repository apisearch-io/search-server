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
use Apisearch\Server\Domain\Query\GetCORSPermissions;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class QueryCORSController.
 */
final class QueryCORSController extends ControllerWithQueryBus
{
    /**
     * Request CORS permissions for query.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $originObject = $this->createOriginByRequest($request);
        $allowedMethod = $request->get('allowed_method', Request::METHOD_GET);

        return $this
            ->ask(new GetCORSPermissions(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                $originObject
            ))
            ->then(function ($origin) use ($allowedMethod) {
                return false === $origin
                    ? $this->createForbiddenResponse()
                    : $this->createPermittedResponse($origin, $allowedMethod);
            });
    }

    /**
     * Create permitted response.
     *
     * @param string $origin
     * @param string $allowedMethod
     *
     * @return Response
     */
    private function createPermittedResponse(
        string $origin,
        string $allowedMethod
    ): Response {
        return new Response(null, 204, [
            'Access-Control-Allow-Origin' => 'null' === $origin
                ? '*'
                : $origin,
            'Access-Control-Allow-Headers' => \implode(', ', [
                'Content-Encoding',
                'Content-Type',
                'Authorization',
            ]),
            'Access-Control-Allow-Methods' => $allowedMethod,
        ]);
    }

    /**
     * Create forbidden response.
     */
    private function createForbiddenResponse()
    {
        return new Response(null, 403);
    }
}
