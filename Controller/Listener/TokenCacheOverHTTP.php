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

use Apisearch\Http\Http;
use Apisearch\Model\Token;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class TokenCacheOverHTTP.
 */
class TokenCacheOverHTTP
{
    /**
     * Add cache control on kernel response.
     *
     * @param FilterResponseEvent $event
     *
     * @return PromiseInterface
     */
    public function addCacheControlOnKernelResponse(FilterResponseEvent $event): PromiseInterface
    {
        return (new FulfilledPromise($event))
            ->then(function (FilterResponseEvent $event) {
                $response = $event->getResponse();
                $request = $event->getRequest();
                $query = $request->query;
                $token = $query->get(Http::TOKEN_FIELD, '');

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
}
