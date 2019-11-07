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

use Elastica\Multi\MultiBuilder;
use Elastica\Multi\Search as ElasticaMultiSearch;
use Elastica\Request;
use Elastica\Response;
use React\Promise\PromiseInterface;

/**
 * Class AsyncMultiSearch.
 */
class AsyncMultiSearch extends ElasticaMultiSearch
{
    /**
     * @return PromiseInterface
     */
    public function searchAsync(): PromiseInterface
    {
        $data = $this->_getData();
        $builder = new MultiBuilder();

        return $this
            ->getClient()
            ->requestAsync(
            '_msearch',
                Request::POST,
                $data,
                $this->_options,
                Request::NDJSON_CONTENT_TYPE
            )
            ->then(function (Response $response) use ($builder) {
                return $builder->buildMultiResultSet(
                    $response,
                    $this->getSearches()
                );
            });
    }
}
