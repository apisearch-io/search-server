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

use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\AppRequiredCommand;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;

/**
 * Class DeleteToken.
 */
class DeleteToken extends CommandWithRepositoryReferenceAndToken implements AppRequiredCommand
{
    /**
     * @var TokenUUID
     *
     * TokenUUID to delete
     */
    private $tokenUUIDToDelete;

    /**
     * AddToken constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param TokenUUID           $tokenUUIDToDelete
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        TokenUUID $tokenUUIDToDelete
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->tokenUUIDToDelete = $tokenUUIDToDelete;
    }

    /**
     * Get Token to delete.
     *
     * @return TokenUUID
     */
    public function getTokenUUIDToDelete(): TokenUUID
    {
        return $this->tokenUUIDToDelete;
    }
}
