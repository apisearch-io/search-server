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

use Apisearch\Http\Http;
use Apisearch\Model\Token;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class TokenCacheOverHTTP.
 */
final class TokenCacheOverHTTP implements EventSubscriberInterface
{
    /**
     * Add cache control on kernel response.
     *
     * @param ResponseEvent $event
     *
     * @return PromiseInterface
     */
    public function addCacheControlOnKernelResponse(ResponseEvent $event): PromiseInterface
    {
        return resolve($event)
            ->then(function (ResponseEvent $event) {
                $response = $event->getResponse();
                $request = $event->getRequest();
                $response->headers->set('Server', 'Apisearch/DriftPHP');
                $attributes = $request->attributes;
                $token = $attributes->get(Http::TOKEN_FIELD, '');

                if (
                    $request->isMethod(Request::METHOD_GET) &&
                    $token instanceof Token &&
                    $token->getTtl() > 0
                ) {
                    $response->setMaxAge($token->getTtl());
                    $response->setSharedMaxAge($token->getTtl());
                    $response->setPublic();
                } else {
                    $response->setMaxAge(0);
                    $response->setSharedMaxAge(0);
                    $response->setPrivate();
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ResponseEvent::class => [
                ['addCacheControlOnKernelResponse', 0],
            ],
        ];
    }
}
