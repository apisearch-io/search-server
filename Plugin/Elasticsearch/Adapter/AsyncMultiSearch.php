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

namespace Apisearch\Plugin\Elasticsearch\Adapter;

use Elastica\Exception\InvalidException;
use Elastica\Multi\ResultSet;
use Elastica\Response;
use Elastica\ResultSet\DefaultBuilder;
use Elasticsearch\Endpoints\Msearch;
use Elasticsearch\Serializers\SmartSerializer;
use React\Promise\PromiseInterface;

/**
 * Class AsyncMultiSearch.
 */
class AsyncMultiSearch extends AsyncAdapter
{
    /**
     * Search in the set indices, types.
     *
     * @param array $queriesArray
     * @param array $params
     *
     * @throws InvalidException
     *
     * @return PromiseInterface<ResultSet>
     */
    public function multisearchAsync(
        array $queriesArray,
        array $params
    ): PromiseInterface {
        $multiSearch = new Msearch(new SmartSerializer());

        $queries = [];
        foreach ($queriesArray as list($indexName, $search)) {
            $query = $search->getQuery();
            $name = $search->getName();
            $bodyAsArray[] = json_encode(['index' => $indexName]);
            $bodyAsArray[] = json_encode($query->toArray());
            $queries[$name] = $query;
        }

        $data = implode("\n", $bodyAsArray)."\n";
        $multiSearch->setBody($data);
        $multiSearch->setParams($params);

        $builder = new DefaultBuilder();

        return $this
            ->getAsyncClient()
            ->requestAsyncEndpoint($multiSearch)
            ->then(function (Response $response) use ($queries, $builder) {
                $resultSets = [];
                $data = $response->getData();
                \reset($queries);

                foreach ($data['responses'] as $responseData) {
                    $search = \current($queries);
                    $key = \key($queries);
                    \next($queries);

                    $resultSets[$key] = $builder->buildResultSet(
                        new Response($responseData), $search
                    );
                }

                return new ResultSet($response, $resultSets);
            });
    }
}
