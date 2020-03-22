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

use Apisearch\Model\Token;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class RouterRedirectionToJsonResponse.
 */
class RouterRedirectionToJsonResponse implements EventSubscriberInterface
{
    /**
     * Intercepting redirects.
     *
     * @param ResponseEvent $event
     *
     * @return PromiseInterface
     */
    public function onKernelResponse(ResponseEvent $event): PromiseInterface
    {
        return resolve($event)
            ->then(function (ResponseEvent $event) {
                $response = $event->getResponse();
                if ($response instanceof RedirectResponse) {
                    if (Response::HTTP_MOVED_PERMANENTLY === $response->getStatusCode()) {
                        $queryAll = $event
                            ->getRequest()
                            ->query
                            ->all();

                        if ($queryAll['token'] instanceof Token) {
                            $queryAll['token'] = $queryAll['token']
                                ->getTokenUUID()
                                ->composeUUID();
                        }

                        $location =
                            explode('?', $response->getTargetUrl())[0].'?'.
                            http_build_query($queryAll);

                        $response->headers->set('location', $location);
                        $event->setResponse(new JsonResponse(
                            [
                                'code' => Response::HTTP_MOVED_PERMANENTLY,
                                'message' => 'Moved Permanently',
                            ],
                            Response::HTTP_MOVED_PERMANENTLY,
                            $response->headers->all()
                        ));
                    }
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
                ['onKernelResponse', 0],
            ],
        ];
    }
}
