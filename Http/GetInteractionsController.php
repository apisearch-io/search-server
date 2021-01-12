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
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetInteractionsController.
 */
final class GetInteractionsController extends ControllerWithQueryBus
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
        $perDay = $request->attributes->get('per_day', false);
        list($from, $to, $days) = $this->getDateRangeFromRequest($request);

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
                $query->get('platform'),
                $userEncrypt->getUUIDByInput($query->get('user_id')),
                $query->get('item_id'),
                InteractionType::resolveAlias($query->get('type')),
                $query->get('count'),
                $userEncrypt->getUUIDByInput($query->get('context')),
            ))
            ->then(function ($interactions) use ($request, $from, $to, $days) {
                return new JsonResponse(
                    [
                        'data' => $interactions,
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
