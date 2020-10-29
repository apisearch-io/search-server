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

use Apisearch\Plugin\Admin\Domain\ImperativeEvents;
use Apisearch\Server\Http\ControllerWithEventBus;
use Exception;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DispatchImperativeEventController.
 */
class DispatchImperativeEventController extends ControllerWithEventBus
{
    /**
     * Send imperative event.
     *
     * @param Request $request
     * @param string  $eventName
     *
     * @return PromiseInterface|Response
     */
    public function __invoke(
        Request $request,
        string $eventName
    ): PromiseInterface {
        $headers = [
            'Access-Control-Allow-Origin' => $request
                ->headers
                ->get('origin', '*'),
            'Vary' => 'Origin',
        ];

        if (!\array_key_exists($eventName, ImperativeEvents::ALIASES)) {
            return new Response('', Response::HTTP_NOT_FOUND, $headers);
        }

        $event = ImperativeEvents::ALIASES[$eventName];

        return $this
            ->dispatch(new $event())
            ->then(function () use ($headers) {
                return new Response('', Response::HTTP_NO_CONTENT, $headers);
            })
            ->otherwise(function (Exception $_) use ($headers) {
                return new Response('', Response::HTTP_NOT_FOUND, $headers);
            });
    }
}
