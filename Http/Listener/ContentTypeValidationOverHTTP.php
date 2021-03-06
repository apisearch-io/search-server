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

use Apisearch\Exception\UnsupportedContentTypeException;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class ContentTypeValidationOverHTTP.
 */
final class ContentTypeValidationOverHTTP implements EventSubscriberInterface
{
    /**
     * @param RequestEvent $event
     *
     * @return PromiseInterface
     */
    public function validateContentTypeOnKernelRequest(RequestEvent $event): PromiseInterface
    {
        return resolve($event)
            ->then(function (RequestEvent $event) {
                $request = $event->getRequest();

                if (!\in_array($request->getMethod(), [
                    Request::METHOD_GET,
                    Request::METHOD_HEAD,
                    Request::METHOD_OPTIONS,
                ])
                    && $request->attributes->has('json')
                    && ('json' !== $request->getContentType())
                    && !empty($request->getContent())
                ) {
                    throw UnsupportedContentTypeException::createUnsupportedContentTypeException();
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
                ['validateContentTypeOnKernelRequest', 16],
            ],
        ];
    }
}
