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
use Apisearch\Server\Domain\Model\InteractionType;
use Apisearch\Server\Domain\Model\UserEncrypt;
use Apisearch\Server\Domain\Query\GetInteractions;
use Apisearch\Server\Domain\Query\GetSearches;
use Apisearch\Server\Domain\Query\GetTopInteractions;
use Apisearch\Server\Domain\Query\GetTopSearches;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionFilter;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesFilter;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetMetricsController.
 */
final class GetMetricsController extends ControllerWithQueryBus
{
    /**
     * @param Request     $request
     * @param UserEncrypt $userEncrypt
     *
     * @return PromiseInterface
     */
    public function __invoke(
        Request $request,
        UserEncrypt $userEncrypt
    ): PromiseInterface {
        $query = $request->query;

        $repositoryReference = RepositoryReference::create(
            RequestAccessor::getAppUUIDFromRequest($request),
            RequestAccessor::getIndexUUIDFromRequest($request)
        );
        $token = RequestAccessor::getTokenFromRequest($request);
        list($from, $to, $days) = $this->getDateRangeFromRequest($request);
        $platform = $query->get('platform');
        $context = $userEncrypt->getUUIDByInput($query->get('context'));
        $userId = $userEncrypt->getUUIDByInput($query->get('user_id'));
        $n = \intval($query->get('n', 10));

        return
            all([
                // Clicks
                $this->ask(new GetInteractions(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, null, InteractionType::CLICK,
                    InteractionFilter::LINES, $context
                )),

                // Top clicks
                $this->ask(new GetTopInteractions(
                    $repositoryReference, $token,
                    $from, $to,
                    $platform, $userId, InteractionType::CLICK, $n
                )),

                // Unique users click
                $this->ask(new GetInteractions(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, null, InteractionType::CLICK,
                    InteractionFilter::UNIQUE_USERS, $context
                )),

                // Searches with results
                $this->ask(new GetSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, false, true,
                    SearchesFilter::LINES
                )),

                // Unique users searching
                $this->ask(new GetSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, false, false,
                    SearchesFilter::UNIQUE_USERS
                )),

                // Searches without results
                $this->ask(new GetSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    true,
                    $platform, $userId, true, false,
                    SearchesFilter::LINES
                )),

                // Top searches with results
                $this->ask(new GetTopSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    $platform, $userId, false, true,
                    $n
                )),

                // Top searches without results
                $this->ask(new GetTopSearches(
                    $repositoryReference, $token,
                    $from, $to,
                    $platform, $userId, true, false,
                    $n
                )),
            ])
            ->then(function (array $results) use ($request, $from, $to, $days) {
                list(
                    $clicks,
                    $topClicks,
                    $uniqueUsersClick,
                    $searchesWithResults,
                    $uniqueUsersSearches,
                    $searchesWithoutResults,
                    $topSearchesWithResults,
                    $topSearchesWithoutResults
                    ) = $results;

                $totalSearchesWithResults = \array_sum($searchesWithResults);
                $totalSearchesWithoutResults = \array_sum($searchesWithoutResults);
                $totalsearches = $totalSearchesWithResults + $totalSearchesWithoutResults;

                return new JsonResponse(
                    [
                        'clicks' => $clicks,
                        'total_clicks' => \array_sum($clicks),
                        'top_clicks' => $topClicks,
                        'unique_users_clicks' => $uniqueUsersClick,
                        'total_unique_users_clicks' => \array_sum($uniqueUsersClick),
                        'searches_with_results' => $searchesWithResults,
                        'total_searches_with_results' => $totalSearchesWithResults,
                        'searches_without_results' => $searchesWithoutResults,
                        'total_searches_without_results' => $totalSearchesWithoutResults,
                        'total_searches' => $totalsearches,
                        'unique_users_searching' => $uniqueUsersSearches,
                        'total_unique_users_searching' => \array_sum($uniqueUsersSearches),
                        'top_searches_with_results' => $topSearchesWithResults,
                        'top_searches_without_results' => $topSearchesWithoutResults,

                        'from' => DateTimeFormatter::formatDateTime($from),
                        'to' => DateTimeFormatter::formatDateTime($to),
                        'days' => $days,
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
