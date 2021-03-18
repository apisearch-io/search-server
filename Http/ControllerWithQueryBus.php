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

use DateTime;
use Drift\CommandBus\Bus\QueryBus;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerWithQueryBus.
 */
abstract class ControllerWithQueryBus extends BaseController
{
    private QueryBus $queryBus;

    /**
     * Controller constructor.
     *
     * @param QueryBus $queryBus
     */
    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @param object $query
     *
     * @return PromiseInterface
     */
    protected function ask($query)
    {
        return $this
            ->queryBus
            ->ask($query);
    }

    /**
     * @param Request $request
     *
     * @return [DateTime, DateTime, int]
     */
    protected function getDateRangeFromRequest(Request $request): array
    {
        $query = $request->query;
        $from = \strval($query->get('from'));
        $to = \strval($query->get('to'));

        return DateTimeFormatter::normalizeRange($from, $to);
    }

    /**
     * Get pagination from request.
     *
     * @param Request $request
     *
     * @return [int, int]
     */
    protected function getPaginationFromRequest(Request $request): array
    {
        $query = $request->query;
        $limit = \intval($query->get('limit', 0));
        $page = \intval($query->get('page', 0));

        return [$limit, $page];
    }
}
