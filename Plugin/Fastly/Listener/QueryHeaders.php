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

namespace Apisearch\Plugin\Fastly\Listener;

use Apisearch\Http\Http;
use function React\Promise\resolve;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class QueryHeaders.
 */
class QueryHeaders implements EventSubscriberInterface
{
    /**
     * @param ResponseEvent $event
     */
    public function addSurrogateKeys(ResponseEvent $event): PromiseInterface
    {
        return resolve()
            ->then(function () use ($event) {
                $request = $event->getRequest();
                $routeName = $request->get('_route');

                if ('apisearch_v1_query' == $routeName) {
                    $this->addSurrogateKeysToQuery($event, false);

                    return;
                }

                if ('apisearch_v1_query_all_indices' == $routeName) {
                    $this->addSurrogateKeysToQuery($event, true);

                    return;
                }
            });
    }

    /**
     * @param ResponseEvent $event
     * @param bool          $allIndices
     *
     * @return void
     */
    private function addSurrogateKeysToQuery(
        ResponseEvent $event,
        bool $allIndices
    ): void {
        $request = $event->getRequest();
        $requestQuery = $request->query;
        $requestAttributes = $request->attributes;

        $token = $requestQuery->get(Http::TOKEN_FIELD);
        $appUUID = $requestAttributes->get('app_id');
        $indexUUID = $requestAttributes->get('index_id');
        if (empty($indexUUID) || '*' === $indexUUID) {
            $allIndices = true;
        }

        $surrogateKeys = [
            'token-'.$token->getTokenUUID()->composeUUID(),
            'app-'.$appUUID,
        ];

        if ($allIndices) {
            $surrogateKeys[] = "all-indices-$appUUID";
        } else {
            $surrogateKeys = \array_merge(
                $surrogateKeys,
                \array_map(function (string $index) {
                    return 'index-'.$index;
                }, \array_map('trim', \explode(',', $indexUUID)))
            );
        }

        $response = $event->getResponse();
        $response->headers->set('Surrogate-Key', \implode(' ', $surrogateKeys));
    }

    /**
     * @return array|void
     */
    public static function getSubscribedEvents()
    {
        return [
            ResponseEvent::class => [
                ['addSurrogateKeys', 0],
            ],
        ];
    }
}
