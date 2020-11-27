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

/**
 * Class ImportIndex.
 */
class ImportIndexByFeed extends ImportIndex
{
    private string $feed;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference $repositoryReference
     * @param Token               $token
     * @param bool                $deleteOldVersions
     * @param string              $versionUUID
     * @param string              $feed
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        bool $deleteOldVersions,
        string $versionUUID,
        string $feed
    ) {
        parent::__construct($repositoryReference, $token, $deleteOldVersions, $versionUUID);
        $this->feed = $feed;
    }

    /**
     * @return string
     */
    public function getFeed(): string
    {
        return $this->feed;
    }
}
