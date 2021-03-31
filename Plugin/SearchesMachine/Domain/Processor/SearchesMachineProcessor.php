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

namespace Apisearch\Plugin\SearchesMachine\Domain\Processor;

use Apisearch\Plugin\DBAL\Domain\SearchesRepository\DBALSearchesRepository;
use Apisearch\Plugin\SearchesMachine\Domain\SearchesMachine;
use Apisearch\Plugin\SearchesMachine\Domain\SearchTransformer;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Model\Origin;
use Clue\React\Mq\Queue;
use Clue\React\Redis\Client;
use React\Promise\PromiseInterface;

/**
 * Class SearchesMachineProcessor.
 */
class SearchesMachineProcessor
{
    private Client $redisClient;
    private DBALSearchesRepository $searchesRepository;
    private string $redisKey;

    /**
     * @param Client                 $redisClient
     * @param DBALSearchesRepository $searchesRepository
     * @param string                 $redisKey
     */
    public function __construct(
        Client $redisClient,
        DBALSearchesRepository $searchesRepository,
        string $redisKey
    ) {
        $this->redisClient = $redisClient;
        $this->searchesRepository = $searchesRepository;
        $this->redisKey = $redisKey;
    }

    /**
     * @return PromiseInterface
     */
    public function ingestAndProcessSearchesFromRedis(): PromiseInterface
    {
        return $this
            ->redisClient
            ->lRange($this->redisKey, 0, 10000000000)
            ->then(function (array $searches) {
                $searchesMachine = new SearchesMachine();
                foreach ($searches as $search) {
                    $searchesMachine->addSearch(SearchTransformer::fromString($search));
                }

                $searchesMachine->compile();

                return $searchesMachine->getSearches();
            })
            ->then(function (array $searches) {
                return Queue::all(5, $searches, function ($search) {
                    return $this
                        ->searchesRepository
                        ->registerSearch(
                            RepositoryReference::createFromComposed("{$search->getAppUUID()}_{$search->getIndexUUID()}"),
                            $search->getUser(),
                            $search->getText(),
                            $search->getNumberOfResults(),
                            new Origin(
                                $search->getHost(),
                                $search->getIp(),
                                $search->getPlatform()
                            ),
                            $search->getWhen()
                        );
                })
                    ->then(function () {
                        return $this->redisClient->del($this->redisKey);
                    });
            });
    }
}
