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

namespace Apisearch\Server\Controller\Listener;

use Apisearch\Exception\UnsupportedContentTypeException;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class ContentTypeValidationOverHTTP.
 */
class ContentTypeValidationOverHTTP implements EventSubscriberInterface
{
    /**
     * Check content type.
     *
     * @param RequestEvent $event
     *
     * @return PromiseInterface
     */
    public function validateContentTypeOnKernelRequest(RequestEvent $event)
    {
        return resolve($event)
            ->then(function (RequestEvent $event) {
                $request = $event->getRequest();

                if (!in_array($request->getMethod(), [
                    Request::METHOD_GET,
                    Request::METHOD_HEAD,
                ]) && ('json' !== $request->getContentType())
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
