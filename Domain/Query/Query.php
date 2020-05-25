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

namespace Apisearch\Server\Domain\Query;

use Apisearch\Model\Token;
use Apisearch\Query\Query as SearchQuery;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;

/**
 * Class Query.
 */
class Query extends CommandWithRepositoryReferenceAndToken implements IndexRequiredCommand
{
    /**
     * @var SearchQuery
     *
     * Query
     */
    private $query;

    /**
     * @var array
     *
     * Parameters
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $origin;

    /**
     * @var string
     */
    private $ip;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param SearchQuery         $query
     * @param array               $parameters
     * @param string              $origin
     * @param string              $ip
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        SearchQuery $query,
        array $parameters = [],
        string $origin = '',
        string $ip = ''
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->query = $query;
        $this->parameters = $parameters;
        $this->origin = $origin;
        $this->ip = $ip;
    }

    /**
     * Get Query.
     *
     * @return SearchQuery
     */
    public function getQuery(): SearchQuery
    {
        return $this->query;
    }

    /**
     * Get parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }

    /**
     * @return string
     */
    public function getIP(): string
    {
        return $this->ip;
    }
}
