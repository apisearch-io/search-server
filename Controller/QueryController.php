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

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Http\Http;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Query\Query;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class QueryController.
 */
class QueryController extends ControllerWithQueryBus
{
    /**
     * Make a query.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     *
     * @throws InvalidFormatException
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $requestQuery = $request->query;
        $queryModel = RequestAccessor::extractQuery($request);
        $origin = $this->createOriginByRequest($request);

        return $this
            ->ask(new Query(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $queryModel,
                $origin,
                \array_filter($requestQuery->all(), function (string $key) {
                    return !\in_array($key, [
                        Http::TOKEN_FIELD,
                    ]);
                }, ARRAY_FILTER_USE_KEY)
            ))
            ->then(function (Result $result) use ($requestQuery, $request) {
                /*
                 * To allow result manipulation during the response returning, and in
                 * order to increase performance, we will save the Result instance as a
                 * query attribute
                 */
                $requestQuery->set('result', $result);

                return new JsonResponse(
                    $result->toArray(),
                    200, [
                        'Access-Control-Allow-Origin' => $request
                            ->headers
                            ->get('origin', '*'),
                        'Vary' => 'Origin',
                    ]
                );
            });
    }
}
