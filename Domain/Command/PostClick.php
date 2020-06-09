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
use Apisearch\Server\Domain\Model\Origin;

/**
 * Class PostClick.
 */
class PostClick extends CommandWithRepositoryReferenceAndToken implements AppRequiredCommand
{
    /**
     * @var string
     */
    private $userUUID;

    /**
     * @var ItemUUID
     */
    private $itemUUID;

    /**
     * @var Origin
     */
    private $origin;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param string              $userUUID
     * @param ItemUUID            $itemUUID
     * @param Origin              $origin
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        string $userUUID,
        ItemUUID $itemUUID,
        Origin $origin
    ) {
        parent::__construct($repositoryReference, $token);
        $this->userUUID = $userUUID;
        $this->itemUUID = $itemUUID;
        $this->origin = $origin;
    }

    /**
     * @return string
     */
    public function getUserUUID(): string
    {
        return $this->userUUID;
    }

    /**
     * @return ItemUUID
     */
    public function getItemUUID(): ItemUUID
    {
        return $this->itemUUID;
    }

    /**
     * @return Origin
     */
    public function getOrigin(): Origin
    {
        return $this->origin;
    }
}