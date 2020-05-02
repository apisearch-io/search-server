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

use Apisearch\Server\Domain\Query\CheckHealth;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CheckHealthController.
 */
class CheckHealthController extends ControllerWithQueryBus
{
    /**
     * Health controller.
     *
     * @param Request $request
     *
     * @return PromiseInterface
     */
    public function __invoke(Request $request): PromiseInterface
    {
        if ($request->query->has('optimize')) {
            \gc_collect_cycles();
        }

        /*
         * @var array
         */
        return $this
            ->ask(new CheckHealth())
            ->then(function (array $health) {
                return new JsonResponse($health);
            });
    }
}
