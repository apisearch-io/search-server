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
use Apisearch\Server\Domain\Query\GetInteractions;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetInteractionsController.
 */
final class GetInteractionsController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $query = $request->query;
        $perDay = $request->attributes->get('per_day', false);
        list($from, $to) = $this->getDateRangeFromRequest($request);

        return $this
            ->ask(new GetInteractions(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $from,
                $to,
                $perDay,
                $query->get('platform', null),
                $query->get('user_id', null),
                $query->get('item_id', null),
                $query->get('type', null),
                $query->get('count', null),
            ))
            ->then(function ($interactions) use ($request) {
                return new JsonResponse(
                    $interactions,
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
