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
use Apisearch\Server\Domain\Query\GetSearches;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetSearchesController.
 */
class GetSearchesController extends ControllerWithQueryBus
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
            ->ask(new GetSearches(
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
                \boolval($query->get('exclude_with_results', false)),
                \boolval($query->get('exclude_without_results', false)),
            ))
            ->then(function ($searches) use ($request) {
                return new JsonResponse(
                    $searches,
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