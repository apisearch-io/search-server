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

use Apisearch\Server\Domain\Query\Ping;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PingController.
 */
class PingController extends ControllerWithQueryBus
{
    /**
     * Ping.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        return $this
            ->queryBus
            ->ask(new Ping())
            ->then(function (bool $alive) {
                return true === $alive
                    ? new JsonResponse('', Response::HTTP_OK)
                    : new JsonResponse('', Response::HTTP_INTERNAL_SERVER_ERROR);
            });
    }
}
