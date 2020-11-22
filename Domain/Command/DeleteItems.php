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

use Apisearch\Model\ItemUUID;
use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Repository\WithRepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;

/**
 * Class DeleteItems.
 */
class DeleteItems extends CommandWithRepositoryReferenceAndToken implements WithRepositoryReference, IndexRequiredCommand
{
    /**
     * @var ItemUUID[]
     */
    private array $itemsUUID;

    /**
     * DeleteCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param ItemUUID[]          $itemsUUID
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        array $itemsUUID
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->itemsUUID = $itemsUUID;
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
