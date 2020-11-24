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

namespace Apisearch\Server\Domain\CommandHandler;

use Apisearch\Server\Domain\Command\ImportIndexByStream;
use Apisearch\Server\Domain\CommandWithRepositoryReferenceAndToken;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ImportIndexByStreamHandler.
 */
class ImportIndexByStreamHandler extends ImportIndexHandler
{
    /**
     * @param ImportIndexByStream $importIndexByStream
     *
     * @return PromiseInterface
     */
    public function handle(ImportIndexByStream $importIndexByStream): PromiseInterface
    {
        return $this->handleByCommand($importIndexByStream);
    }

    /**
     * @param CommandWithRepositoryReferenceAndToken $command
     *
     * @return PromiseInterface<ImportIndexByStream>
     */
    protected function getImportIndexByStream(CommandWithRepositoryReferenceAndToken $command): PromiseInterface
    {
        return resolve($command);
    }
}
