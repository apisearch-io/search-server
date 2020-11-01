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
use Apisearch\Query\Query as QueryModel;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;
use Apisearch\Server\Domain\Model\Origin;

/**
 * Class Query.
 */
class Query extends CommandWithRepositoryReferenceAndToken implements IndexRequiredCommand
{
    private QueryModel $query;
    private Origin $origin;
    private ?string $userId;
    private array $parameters;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param QueryModel          $query
     * @param Origin              $origin
     * @param string|null         $userId
     * @param array               $parameters
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        QueryModel $query,
        Origin $origin,
        ?string $userId,
        array $parameters = []
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->query = $query;
        $this->origin = $origin;
        $this->userId = $userId;
        $this->parameters = $parameters;
    }

    /**
     * @return QueryModel
     */
    public function getQuery(): QueryModel
    {
        return $this->query;
    }

    /**
     * @return Origin
     */
    public function getOrigin(): Origin
    {
        return $this->origin;
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
