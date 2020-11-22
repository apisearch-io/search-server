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

use Apisearch\Model\ItemUUID;
use Apisearch\Model\Token;
use Apisearch\Query\Query as Query;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Repository\WithRepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;

class GetSimilarItems extends CommandWithRepositoryReferenceAndToken implements WithRepositoryReference, IndexRequiredCommand
{
    /**
     * @var ItemUUID[]
     */
    private array $itemsUUID;
    private Query $query;

    /**
     * DeleteCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param Query               $query
     * @param ItemUUID[]          $itemsUUID
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        Query $query,
        array $itemsUUID
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->query = $query;
        $this->itemsUUID = $itemsUUID;
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Get Items.
     *
     * @return ItemUUID[]
     */
    public function getItemsUUID(): array
    {
        return $this->itemsUUID;
    }
}
