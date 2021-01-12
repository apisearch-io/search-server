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
 * Class PostInteraction.
 */
class PostInteraction extends CommandWithRepositoryReferenceAndToken implements AppRequiredCommand
{
    private string $userUUID;
    private ItemUUID $itemUUID;
    private int $position;
    private Origin $origin;
    private ?string $context;
    private string $interactionType;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param string              $userUUID
     * @param ItemUUID            $itemUUID
     * @param int                 $position
     * @param string|null         $context
     * @param Origin              $origin
     * @param string              $interactionType
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        string $userUUID,
        ItemUUID $itemUUID,
        int $position,
        ?string $context,
        Origin $origin,
        string $interactionType
    ) {
        parent::__construct($repositoryReference, $token);
        $this->userUUID = $userUUID;
        $this->itemUUID = $itemUUID;
        $this->position = $position;
        $this->context = $context;
        $this->origin = $origin;
        $this->interactionType = $interactionType;
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
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * @return Origin
     */
    public function getOrigin(): Origin
    {
        return $this->origin;
    }

    /**
     * @return string
     */
    public function getInteractionType(): string
    {
        return $this->interactionType;
    }
}
