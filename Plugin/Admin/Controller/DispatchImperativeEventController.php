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

use Apisearch\Plugin\Admin\Domain\ImperativeEvents;
use Apisearch\Server\Controller\ControllerWithEventBus;
use Exception;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DispatchImperativeEventController.
 */
class DispatchImperativeEventController extends ControllerWithEventBus
{
    /**
     * Send imperative event.
     *
     * @param string $eventName
     *
     * @return PromiseInterface|Response
     */
    public function __invoke(string $eventName)
    {
        if (!\array_key_exists($eventName, ImperativeEvents::ALIASES)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $event = ImperativeEvents::ALIASES[$eventName];

        return $this
            ->dispatch(new $event())
            ->then(function () {
                return new Response('', Response::HTTP_NO_CONTENT);
            })
            ->otherwise(function (Exception $_) {
                return new Response('', Response::HTTP_NOT_FOUND);
            });
    }
}
