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
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\AppRequiredCommand;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;

/**
 * Class PutToken.
 */
class PutToken extends CommandWithRepositoryReferenceAndToken implements AppRequiredCommand
{
    private Token $newToken;

    /**
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param Token               $newToken
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        Token $newToken
    ) {
        parent::__construct(
            $repositoryReference,
            $token
        );

        $this->newToken = $newToken;
    }

    /**
     * Get new Token.
     *
     * @return Token
     */
    public function getNewToken(): Token
    {
        return $this->newToken;
    }
}
