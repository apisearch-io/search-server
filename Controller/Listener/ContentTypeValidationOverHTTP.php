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
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class ContentTypeValidationOverHTTP.
 */
class ContentTypeValidationOverHTTP
{
    /**
     * Check content type.
     *
     * @param GetResponseEvent $event
     *
     * @return PromiseInterface
     */
    public function validateContentTypeOnKernelRequest(GetResponseEvent $event)
    {
        return (new FulfilledPromise($event))
            ->then(function (GetResponseEvent $event) {
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
}
