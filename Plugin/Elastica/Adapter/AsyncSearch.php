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

namespace Apisearch\Plugin\Elastica\Adapter;

use Elastica\Request;
use Elastica\Response;
use Elastica\ResultSet\DefaultBuilder;
use Elastica\Search as ElasticaSearch;
use React\Promise\PromiseInterface;

/**
 * Class AsyncSearch.
 */
class AsyncSearch extends ElasticaSearch
{
    /**
     * Search in the set indices, types.
     *
     * @param mixed     $query
     * @param int|array $options OPTIONAL Limit or associative array of options (option=>value)
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return PromiseInterface
     */
    public function searchAsync($query = '', $options = null): PromiseInterface
    {
        $this->setOptionsAndQuery($options, $query);
        $query = $this->getQuery();
        $path = $this->getPath();
        $params = $this->getOptions();
        $data = $query->toArray();
        $builder = new DefaultBuilder();

        return $this
            ->getClient()
            ->requestAsync(
                $path,
                Request::GET,
                $data,
                $params
            )
            ->then(function (Response $response) use ($query, $builder) {
                return $builder->buildResultSet($response, $query);
            });
    }
}
