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

namespace Apisearch\Server\Domain;

use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Repository\RepositoryReference;

/**
 * Class CommandWithRepositoryReferenceAndTokenAndIndexUUID.
 */
abstract class CommandWithRepositoryReferenceAndTokenAndIndexUUID extends CommandWithRepositoryReferenceAndToken
{
    private IndexUUID $indexUUID;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param IndexUUID           $indexUUID
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        IndexUUID $indexUUID
    ) {
        parent::__construct($repositoryReference, $token);
        $this->indexUUID = $indexUUID;
    }

    /**
     * Get IndexUUID.
     *
     * @return IndexUUID
     */
    public function getIndexUUID(): IndexUUID
    {
        return $this->indexUUID;
    }
}
