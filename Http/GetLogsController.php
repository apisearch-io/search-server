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
use Apisearch\Server\Domain\Query\GetLogs;
use Apisearch\Server\Domain\Repository\LogRepository\LogFilter;
use Apisearch\Server\Domain\Repository\LogRepository\LogWithText;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GetLogsController.
 */
final class GetLogsController extends ControllerWithQueryBus
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        list($from, $to, $days) = $this->getDateRangeFromRequest($request);
        $types = $request->query->get('types');
        $types = \is_array($types) ? $types : [];
        list($limit, $page) = $this->getPaginationFromRequest($request);

        $repositoryReference = RepositoryReference::create(
            RequestAccessor::getAppUUIDFromRequest($request),
            RequestAccessor::getIndexUUIDFromRequest($request)
        );

        return $this
            ->ask(new GetLogs(
                $repositoryReference,
                RequestAccessor::getTokenFromRequest($request),
                LogFilter::create($repositoryReference)
                    ->from($from)
                    ->to($to)
                    ->fromTypes($types)
                    ->paginate($limit, $page)
            ))
            ->then(function (array $logsWithText) use ($request, $from, $to, $days) {
                return new JsonResponse(
                    [
                        'data' => $this->logsWithTextToArray($logsWithText),
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

    /**
     * @param array $logsWithText
     *
     * @return array
     */
    private function logsWithTextToArray(array $logsWithText): array
    {
        return \array_map(function (LogWithText $logWithText) {
            return $logWithText->toArray();
        }, $logsWithText);
    }
}
