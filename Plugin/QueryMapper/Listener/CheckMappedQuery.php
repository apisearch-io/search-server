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

namespace Apisearch\Plugin\QueryMapper\Listener;

use Apisearch\Plugin\QueryMapper\Domain\QueryMapperLoader;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class CheckMappedQuery.
 */
class CheckMappedQuery
{
    private QueryMapperLoader $queryMapperLoader;

    /**
     * CheckMappingQueries constructor.
     *
     * @param QueryMapperLoader $queryMapperLoader
     */
    public function __construct(QueryMapperLoader $queryMapperLoader)
    {
        $this->queryMapperLoader = $queryMapperLoader;
    }

    /**
     * On kernel async request.
     *
     * @param RequestEvent $event
     *
     * @return PromiseInterface
     */
    public function onKernelAsyncRequest(RequestEvent $event): PromiseInterface
    {
        $request = $event->getRequest();
        $route = $request->get('_route');

        if (!\in_array($route, [
            'apisearch_v1_query',
            'apisearch_v1_query_all_indices',
        ])) {
            return resolve();
        }

        return
            resolve()
            ->then(function () use ($event) {
                $this
                    ->queryMapperLoader
                    ->fulfillRequestWithQueryAndCredentials(
                        $event->getRequest()
                    );
            });
    }
}
