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

namespace Apisearch\Server\Domain\Command;

use Apisearch\Model\Changes;
use Apisearch\Model\Token;
use Apisearch\Query\Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Repository\WithRepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;

/**
 * Class UpdateItems.
 */
class UpdateItems extends CommandWithRepositoryReferenceAndToken implements WithRepositoryReference, IndexRequiredCommand
{
    /**
     * @var Query
     *
     * Query
     */
    private $query;

    /**
     * @var Changes
     *
     * Changes
     */
    private $changes;

    /**
     * IndexCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param Query               $query
     * @param Changes             $changes
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        Query $query,
        Changes $changes
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->query = $query;
        $this->changes = $changes;
    }

    /**
     * Get query.
     *
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Get changes.
     *
     * @return Changes
     */
    public function getChanges(): Changes
    {
        return $this->changes;
    }
}
