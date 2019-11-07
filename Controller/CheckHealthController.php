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

/**
 * Class CheckHealthController.
 */
class CheckHealthController extends ControllerWithBus
{
    /**
     * Health controller.
     *
     * @return PromiseInterface
     */
    public function __invoke(): PromiseInterface
    {
        /*
         * @var array
         */
        return $this
            ->commandBus
            ->handle(new CheckHealth())
            ->then(function (array $health) {
                return new JsonResponse($health);
            });
    }
}
