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

namespace Apisearch\Plugin\Admin\Controller;

use Apisearch\Plugin\Admin\Domain\Command\OptimizeUsageLines;
use DateTime;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OptimizeUsageLinesController.
 */
class OptimizeUsageLinesController extends ControllerWithCommandBusAsGod
{
    /**
     * @param Request $request
     *
     * @return PromiseInterface|Response
     */
    public function __invoke(Request $request)
    {
        $query = $request->query;
        $from = DateTime::createFromFormat('Ymd', \strval($query->get('from', '')));
        $to = DateTime::createFromFormat('Ymd', \strval($query->get('to', '')));

        if (
            !$from instanceof DateTime ||
            !$to instanceof DateTime
        ) {
            return new Response(400, [], 'Query parameters "from" and "to" should have valid DateTime values in format Ymd');
        }

        return $this
            ->execute(new OptimizeUsageLines(
                $from,
                $to
            ))
            ->then(function () {
                return new Response(204);
            });
    }
}
