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

namespace Apisearch\Server\Http\Listener;

use Apisearch\Exception\PayloadTooLargeException;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class PayloadTooLargeChecker.
 */
class PayloadTooLargeChecker implements EventSubscriberInterface
{
    /**
     * @param RequestEvent $event
     *
     * @return PromiseInterface
     */
    public function validatePayloadTooLarge(RequestEvent $event): PromiseInterface
    {
        return resolve($event)
            ->then(function (RequestEvent $event) {
                $request = $event->getRequest();
                if (
                    \intval($request->headers->get('Content-Length')) > 0 &&
                    empty($request->getContent())
                ) {
                    throw PayloadTooLargeException::create();
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => [
                ['validatePayloadTooLarge', 32],
            ],
        ];
    }
}
