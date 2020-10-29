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

namespace Apisearch\Plugin\Admin\Http;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Query\GetUsage;
use Apisearch\Server\Http\ControllerWithQueryBusAsGod;
use Apisearch\Server\Http\RequestAccessor;
use DateTime;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetUsageController.
 */
class GetUsageController extends ControllerWithQueryBusAsGod
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
        $from = $query->get('from');
        $from = $from ? DateTime::createFromFormat('Ymd', $from) : new DateTime('first day of this month');
        $to = $query->get('to');
        $to = $to ? DateTime::createFromFormat('Ymd', $to) : null;

        return $this
            ->ask(new GetUsage(
                RepositoryReference::create(
                    AppUUID::createById('*'),
                    IndexUUID::createById('*')
                ),
                RequestAccessor::getTokenFromRequest($request),
                $from,
                $to,
                $query->get('event', null),
                $perDay
            ))
            ->then(function (array $usage) use ($request) {
                return new JsonResponse(
                    $usage,
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
