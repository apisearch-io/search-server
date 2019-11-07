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

use Apisearch\Http\Http;
use Apisearch\Model\Token;
use Apisearch\Plugin\QueryMapper\Domain\ResultMapperLoader;
use Apisearch\Result\Result;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class CheckMappedResult.
 */
class CheckMappedResult
{
    /**
     * @var ResultMapperLoader
     *
     * Result mapper loader
     */
    private $resultMapperLoader;

    /**
     * CheckMappingQueries constructor.
     *
     * @param ResultMapperLoader $resultMapperLoader
     */
    public function __construct(ResultMapperLoader $resultMapperLoader)
    {
        $this->resultMapperLoader = $resultMapperLoader;
    }

    /**
     * On kernel async response.
     *
     * @param FilterResponseEvent $event
     *
     * @return PromiseInterface
     */
    public function onKernelAsyncResponse(FilterResponseEvent $event): PromiseInterface
    {
        return
            (new FulfilledPromise())
            ->then(function () use ($event) {
                $request = $event->getRequest();
                $route = $request->get('_route');

                if (
                    !in_array($route, [
                        'apisearch_v1_query',
                        'apisearch_v1_query_all_indices',
                    ]) ||
                    !$request->query->get(Http::TOKEN_FIELD) instanceof Token ||
                    !$request->get('result') instanceof Result
                ) {
                    return;
                }

                $response = $this
                    ->resultMapperLoader
                    ->getArrayFromResult(
                        $request->query->get(Http::TOKEN_FIELD)->getTokenUUID(),
                        $request->get('result')
                    );

                if (is_array($response)) {
                    $event->setResponse(
                        new JsonResponse(
                            $response,
                            200,
                            [
                                'Access-Control-Allow-Origin' => '*',
                            ]
                        )
                    );
                }
            });
    }
}
