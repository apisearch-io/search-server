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
use Apisearch\Server\Domain\Model\UserEncrypt;
use Apisearch\Server\Domain\Query\GetTopSearches;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetTopSearchesController.
 */
final class GetTopSearchesController extends ControllerWithQueryBus
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
        list($from, $to, $days) = $this->getDateRangeFromRequest($request);

        return $this
            ->ask(new GetTopSearches(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $from,
                $to,
                $query->get('platform', null),
                $userEncrypt->getUUIDByInput($query->get('user_id')),
                \boolval($query->get('exclude_with_results', false)),
                \boolval($query->get('exclude_without_results', false)),
                \intval($query->get('n', 10))
            ))
            ->then(function ($searches) use ($request, $from, $to, $days) {
                return new JsonResponse(
                    [
                        'data' => $searches,
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
