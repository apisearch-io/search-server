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
use Apisearch\Server\Domain\Query\GetPurchases;
use Apisearch\Server\Domain\Repository\PurchaseRepository\PurchaseFilter;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetPurchasesController.
 */
final class GetPurchasesController extends ControllerWithQueryBus
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
            ->ask(new GetPurchases(
                RepositoryReference::create(
                    RequestAccessor::getAppUUIDFromRequest($request),
                    RequestAccessor::getIndexUUIDFromRequest($request)
                ),
                RequestAccessor::getTokenFromRequest($request),
                $from,
                $to,
                $perDay,
                $userEncrypt->getUUIDByInput($query->get('user_id')),
                $query->get('item_id'),
                ($query->get('count') ?? PurchaseFilter::LINES),
            ))
            ->then(function ($purchases) use ($request, $from, $to, $days) {
                return new JsonResponse(
                    [
                        'data' => $purchases,
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
