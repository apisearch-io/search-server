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
use React\Stream\ReadableStreamInterface;

/**
 * Class ImportIndexByStream.
 */
class ImportIndexByStream extends CommandWithRepositoryReferenceAndToken
{
    private ReadableStreamInterface $stream;
    private bool $deleteOldVersions;
    private string $versionUUID;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference     $repositoryReference
     * @param Token                   $token
     * @param ReadableStreamInterface $stream
     * @param bool                    $deleteOldVersions
     * @param string                  $versionUUID
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        ReadableStreamInterface $stream,
        bool $deleteOldVersions,
        string $versionUUID
    ) {
        parent::__construct($repositoryReference, $token);
        $this->stream = $stream;
        $this->deleteOldVersions = $deleteOldVersions;
        $this->versionUUID = $versionUUID;
    }

    /**
     * @return ReadableStreamInterface
     */
    public function getStream(): ReadableStreamInterface
    {
        return $this->stream;
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
