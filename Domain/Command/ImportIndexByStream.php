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
use React\Stream\ReadableStreamInterface;

/**
 * Class ImportIndexByStream.
 */
class ImportIndexByStream extends ImportIndex
{
    private ReadableStreamInterface $stream;

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
        bool $deleteOldVersions,
        string $versionUUID,
        ReadableStreamInterface $stream
    ) {
        parent::__construct($repositoryReference, $token, $deleteOldVersions, $versionUUID);
        $this->stream = $stream;
    }

    /**
     * @return ReadableStreamInterface
     */
    public function getStream(): ReadableStreamInterface
    {
        return $this->stream;
    }
}
