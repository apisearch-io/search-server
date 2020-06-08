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

use DateTime;
use DateTimeZone;
use Drift\CommandBus\Bus\QueryBus;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerWithQueryBus.
 */
abstract class ControllerWithQueryBus extends BaseController
{
    /**
     * @var QueryBus
     *
     * Query bus
     */
    protected $queryBus;

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
     * Get from-to range from request.
     *
     * @param Request $request
     *
     * @return [DateTime|null, DateTime|null]
     */
    protected function getDateRangeFromRequest(Request $request): array
    {
        $query = $request->query;
        $from = $query->get('from');
        $from = $from
            ? DateTime::createFromFormat('Ymd', $from, new DateTimeZone('UTC'))
            : (new DateTime('first day of this month', new DateTimeZone('UTC')))->setTime(0, 0, 0);

        $to = $query->get('to');
        $to = $to
            ? DateTime::createFromFormat('Ymd', $to, new DateTimeZone('UTC'))
            : null;

        return [$from, $to];
    }
}
