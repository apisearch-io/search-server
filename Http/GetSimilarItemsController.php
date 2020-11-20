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
use Apisearch\Model\ItemUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Result\Result;
use Apisearch\Server\Domain\Query\GetSimilarItems;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetSimilarItemsController.
 */
class GetSimilarItemsController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $query = RequestAccessor::extractQuery($request, 'query');
        $requestAttributes = $request->attributes;
        $itemsUUIDAsArray = RequestAccessor::extractRequestContentObject(
            $request,
            'items_uuid',
            InvalidFormatException::itemUUIDRepresentationNotValid($request->getContent()),
            []
        );

        return $this
            ->ask(new GetSimilarItems(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request),
                ),
                RequestAccessor::getTokenFromRequest($request),
                $query,
                \array_map(function (array $object) {
                    return ItemUUID::createFromArray($object);
                }, $itemsUUIDAsArray)
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
