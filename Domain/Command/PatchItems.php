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

use Apisearch\Model\Item;
use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Repository\WithRepositoryReference;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use Apisearch\Server\Domain\IndexRequiredCommand;

/**
 * Class PatchItems.
 */
class PatchItems extends CommandWithRepositoryReferenceAndToken implements WithRepositoryReference, IndexRequiredCommand
{
    /**
     * @var Item[]
     */
    private array $partialItems;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param Item[]              $partialItems
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        array $partialItems
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->partialItems = $partialItems;
    }

    /**
     * Get partial Items.
     *
     * @return Item[]
     */
    public function getPartialItems(): array
    {
        return $this->partialItems;
    }
}
