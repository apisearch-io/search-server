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
    /**
     * @var ReadableStreamInterface
     */
    private $stream;

    /**
     * ResetCommand constructor.
     *
     * @param RepositoryReference     $repositoryReference
     * @param Token                   $token
     * @param ReadableStreamInterface $stream
     */
    public function __construct(
        RepositoryReference $repositoryReference,
        Token $token,
        ReadableStreamInterface $stream
    ) {
        parent::__construct($repositoryReference, $token);
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
