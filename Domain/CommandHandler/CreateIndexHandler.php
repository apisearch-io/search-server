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

use Apisearch\Server\Domain\Command\CreateIndex;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class CreateIndexHandler.
 */
class CreateIndexHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * Create the index.
     *
     * @param CreateIndex $createIndex
     *
     * @return PromiseInterface
     */
    public function handle(CreateIndex $createIndex): PromiseInterface
    {
        return $this
            ->appRepository
            ->createIndex(
                $createIndex->getRepositoryReference(),
                $createIndex->getIndexUUID(),
                $createIndex->getConfig()
            );
    }
}
