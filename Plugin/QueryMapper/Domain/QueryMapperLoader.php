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

namespace Apisearch\Plugin\QueryMapper\Domain;

use Apisearch\Server\Http\RequestAccessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class QueryMapperLoader.
 */
class QueryMapperLoader
{
    private QueryMappers $queryMappers;

    /**
     * Load query mappers.
     *
     * @param array $namespaces
     */
    public function __construct(array $namespaces)
    {
        $this->queryMappers = new QueryMappers();
        foreach ($namespaces as $namespace) {
            $this
                ->queryMappers
                ->addQueryMapper(new $namespace());
        }
    }

    /**
     * Get QueryMappers.
     *
     * @return QueryMappers
     */
    public function getQueryMappers(): QueryMappers
    {
        return $this->queryMappers;
    }

    /**
     * Having a Request query parameters, build a Query and fulfill credentials
     * if needed.
     *
     * @param Request $request
     */
    public function fulfillRequestWithQueryAndCredentials(Request $request)
    {
        $requestQuery = $request->query;
        $token = RequestAccessor::getTokenFromRequest($request);

        $queryMapper = $this
            ->queryMappers
            ->findQueryMapperByToken($token->getTokenUUID()->composeUUID());

        if (!$queryMapper instanceof QueryMapper) {
            return;
        }

        $repositoryReference = $queryMapper->getRepositoryReference();
        $requestQuery->set('index_id', $repositoryReference->getIndexUUID()->composeUUID());
        RequestAccessor::setQuery($request, $queryMapper->buildQueryByRequest($request));
    }
}
