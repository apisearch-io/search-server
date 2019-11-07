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

use Apisearch\Server\Domain\Command\ConfigureIndex;
use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\Event\IndexWasConfigured;
use Apisearch\Server\Domain\WithAppRepositoryAndEventPublisher;
use React\Promise\PromiseInterface;

/**
 * Class ConfigIndexHandler.
 */
class ConfigureIndexHandler extends WithAppRepositoryAndEventPublisher
{
    /**
     * Configure the index.
     *
     * @param ConfigureIndex $configureIndex
     *
     * @return PromiseInterface
     */
    public function handle(ConfigureIndex $configureIndex): PromiseInterface
    {
        $repositoryReference = $configureIndex->getRepositoryReference();
        $indexUUID = $configureIndex->getIndexUUID();
        $config = $configureIndex->getConfig();

        return $this
            ->appRepository
            ->configureIndex(
                $repositoryReference,
                $indexUUID,
                $config
            )
            ->then(function () use ($repositoryReference, $indexUUID, $config) {
                return $this
                    ->eventPublisher
                    ->publish(new DomainEventWithRepositoryReference(
                        $repositoryReference,
                        new IndexWasConfigured(
                            $indexUUID,
                            $config
                        )
                    ));
            });
    }
}
