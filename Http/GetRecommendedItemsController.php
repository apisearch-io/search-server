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

use Apisearch\Query\SortBy;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Model\UserEncrypt;
use Apisearch\Server\Domain\Query\GetRecommendedItems;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetRecommendedItemsController.
 */
class GetRecommendedItemsController extends ControllerWithQueryBus
{
    /**
     * @param Request     $request
     * @param UserEncrypt $userEncrypt
     */
    public function __invoke(
        Request $request,
        UserEncrypt $userEncrypt
    ): PromiseInterface {
        $query = $request->query;
        $modelQuery = RequestAccessor::extractQuery($request, 'query');
        $requestAttributes = $request->attributes;
        $origin = $this->createOriginByRequest($request);

        /*
         * Default behavior it to ask random elements.
         * This behavior can be changed in a middleware
         */
        $modelQuery->sortBy(SortBy::create()->byValue(SortBy::AL_TUN_TUN));

        return $this
            ->ask(new GetRecommendedItems(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request),
                ),
                RequestAccessor::getTokenFromRequest($request),
                $modelQuery,
                $userEncrypt->getUUIDByInput($query->get('user_id')),
                $origin
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
