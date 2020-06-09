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
use Apisearch\Server\Domain\Model\InteractionType;
use Apisearch\Server\Domain\Query\GetInteractions;
use Apisearch\Server\Domain\Query\GetSearches;
use Apisearch\Server\Domain\Query\GetTopInteractions;
use Apisearch\Server\Domain\Query\GetTopSearches;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetMetricsController.
 */
class GetMetricsController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        $query = $request->query;

        $repositoryReference = RepositoryReference::create(
            RequestAccessor::getAppUUIDFromRequest($request),
            RequestAccessor::getIndexUUIDFromRequest($request)
        );
        $token = RequestAccessor::getTokenFromRequest($request);
        list($from, $to) = $this->getDateRangeFromRequest($request);
        $platform = $query->get('platform', null);
        $userId = $query->get('user_id', null);
        $n = \intval($query->get('n', 10));

        return
            all([
                $this->ask(new GetInteractions(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, null, InteractionType::CLICK
                )),
                $this->ask(new GetTopInteractions(
                    $repositoryReference, $token,
                    $from, $to,
                    $platform, $userId, InteractionType::CLICK, $n
                )),
                $this->ask(new GetSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, false, true
                )),
                $this->ask(new GetSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, true, false
                )),
                $this->ask(new GetTopSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    $platform, $userId, false, true,
                    $n
                )),
                $this->ask(new GetTopSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    $platform, $userId, true, false,
                    $n
                )),
            ])
            ->then(function (array $results) use ($request) {
                list(
                    $interactions,
                    $topClicks,
                    $searchesWithResults,
                    $searchesWithoutResults,
                    $topSearchesWithResults,
                    $topSearchesWithoutResults
                    ) = $results;

                return new JsonResponse(
                    [
                        'interactions' => $interactions,
                        'top_clicks' => $topClicks,
                        'searches_with_results' => $searchesWithResults,
                        'searches_without_results' => $searchesWithoutResults,
                        'top_searches_with_results' => $topSearchesWithResults,
                        'top_searches_without_results' => $topSearchesWithoutResults,
                    ],
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
