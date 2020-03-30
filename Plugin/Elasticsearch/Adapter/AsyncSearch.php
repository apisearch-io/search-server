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
use Elastica\Query;
use Elastica\Response;
use Elastica\ResultSet;
use Elastica\ResultSet\DefaultBuilder;
use Elasticsearch\Endpoints\Search;
use React\Promise\PromiseInterface;

/**
 * Class AsyncSearch.
 */
class AsyncSearch extends AsyncAdapter
{
    /**
     * Search in the set indices, types.
     *
     * @param Query  $query
     * @param array  $params
     * @param string $index
     *
     * @throws InvalidException
     *
     * @return PromiseInterface<ResultSet>
     */
    public function searchAsync(
        Query $query,
        array $params,
        string $index
    ): PromiseInterface {
        $search = new Search();
        $search->setBody($query->toArray());
        $search->setParams($params);
        $builder = new DefaultBuilder();

        return $this
            ->getAsyncClient()
            ->requestAsyncEndpoint($search, $index)
            ->then(function (Response $response) use ($query, $builder) {
                return $builder->buildResultSet($response, $query);
            });
    }
}
