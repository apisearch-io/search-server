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
use Apisearch\Server\Domain\Event\IndexWasConfigured;
use Apisearch\Server\Domain\WithConfigRepositoryAppRepositoryAndEventPublisher;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ConfigureIndexHandler.
 */
class ConfigureIndexHandler extends WithConfigRepositoryAppRepositoryAndEventPublisher
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

        $currentMetadataConfig = $this
            ->configRepository
            ->getConfig($repositoryReference);

        /*
         * We should reload the index only if the hash changed
         */
        return all([
                $this
                    ->configRepository
                    ->putConfig($repositoryReference, $config),

                (
                    !$configureIndex->forceReindex() &&
                    $this->configHashesAreEqual($config, $currentMetadataConfig)
                )
                    ? resolve(false)
                    : $this
                        ->appRepository
                        ->configureIndex(
                            $repositoryReference,
                            $indexUUID,
                            $config
                        )
                        ->then(function () {
                            return true;
                        }),
            ])
            ->then(function (array $results) use ($repositoryReference, $indexUUID, $config) {
                $indexWasReindexed = $results[1];

                return $this
                    ->eventBus
                    ->dispatch(
                        (new IndexWasConfigured(
                            $indexUUID,
                            $config,
                            $indexWasReindexed
                        ))
                            ->withRepositoryReference($repositoryReference)
                    );
            });
    }
}
