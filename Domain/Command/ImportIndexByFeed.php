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
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;

/**
 * Class ImportIndex.
 */
class ImportIndexByFeed extends CommandWithRepositoryReferenceAndToken
{
    private string $feed;
    private bool $deleteOldVersions;
    private string $versionUUID;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param string              $feed
     * @param bool                $deleteOldVersions
     * @param string              $versionUUID
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        string $feed,
        bool $deleteOldVersions,
        string $versionUUID
    ) {
        parent::__construct($repositoryReference, $token);
        $this->feed = $feed;
        $this->deleteOldVersions = $deleteOldVersions;
        $this->versionUUID = $versionUUID;
    }

    /**
     * @return string
     */
    public function getFeed(): string
    {
        return $this->feed;
    }

    /**
     * @return bool
     */
    public function shouldDeleteOldVersions(): bool
    {
        return $this->deleteOldVersions;
    }

    /**
     * @return string
     */
    public function getVersionUUID(): string
    {
        return $this->versionUUID;
    }
}
