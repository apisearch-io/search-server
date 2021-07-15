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
use Apisearch\Server\Domain\AppRequiredCommand;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;

/**
 * Class PostPurchase.
 */
class PostPurchase extends CommandWithRepositoryReferenceAndToken implements AppRequiredCommand
{
    /**
     * @var ItemUUID[]
     */
    private array $itemsUUID;
    private string $userUUID;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param string              $userUUID
     * @param ItemUUID[]          $itemsUUID
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        string $userUUID,
        array $itemsUUID
    ) {
        parent::__construct($repositoryReference, $token);
        $this->userUUID = $userUUID;
        $this->itemsUUID = $itemsUUID;
    }

    /**
     * @return string
     */
    public function getUserUUID(): string
    {
        return $this->userUUID;
    }

    /**
     * @return ItemUUID[]
     */
    public function getItemsUUID(): array
    {
        return $this->itemsUUID;
    }
}
