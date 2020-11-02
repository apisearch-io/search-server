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

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Http\Http;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Model\UserEncrypt;
use Apisearch\Server\Domain\Query\Query;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class QueryController.
 */
final class QueryController extends ControllerWithQueryBus
{
    /**
     * @param Request     $request
     * @param UserEncrypt $userEncrypt
     *
     * @return PromiseInterface
     *
     * @throws InvalidFormatException
     */
    public function __invoke(
        Request $request,
        UserEncrypt $userEncrypt
    ): PromiseInterface {
        $requestQuery = $request->query;
        $requestAttributes = $request->attributes;
        $queryModel = RequestAccessor::extractQuery($request);
        $origin = $this->createOriginByRequest($request);

        $parameters = \array_merge(
            $requestAttributes->all(),
            \array_filter($requestQuery->all(), function (string $key) {
                return !\in_array($key, [
                    Http::TOKEN_FIELD,
                ]);
            }, ARRAY_FILTER_USE_KEY)
        );

        return $this
            ->ask(new Query(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $queryModel,
                $origin,
                $userEncrypt->getUUIDByInput($requestQuery->get('user_id'), $origin),
                $parameters
            ))
            ->then(function (Result $result) use ($requestAttributes, $request) {
                /*
                 * To allow result manipulation during the response returning, and in
                 * order to increase performance, we will save the Result instance as a
                 * query attribute
                 */
                $requestAttributes->set('result', $result);

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
