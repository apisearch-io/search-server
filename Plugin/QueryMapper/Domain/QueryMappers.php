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

/**
 * Class QueryMappers.
 */
class QueryMappers
{
    /**
     * @var QueryMapper[]
     *
     * Query mappers
     */
    private $queryMappers = [];

    /**
     * Add query mapper.
     *
     * @param QueryMapper
     *
     * @return void
     */
    public function addQueryMapper(QueryMapper $queryMapper): void
    {
        $this->queryMappers[] = $queryMapper;
    }

    /**
     * Find query mapper by token.
     *
     * @param string $token
     *
     * @return QueryMapper|null
     */
    public function findQueryMapperByToken(string $token): ? QueryMapper
    {
        foreach ($this->queryMappers as $queryMapper) {
            if (\in_array($token, $queryMapper->getMappingTokens())) {
                return $queryMapper;
            }
        }

        return null;
    }
}
