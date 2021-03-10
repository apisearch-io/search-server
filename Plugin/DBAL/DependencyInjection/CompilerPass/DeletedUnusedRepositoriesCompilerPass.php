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

namespace Apisearch\Plugin\DBAL\DependencyInjection\CompilerPass;

use Apisearch\Plugin\DBAL\Domain\AppRepository\DBALTokenRepository;
use Apisearch\Plugin\DBAL\Domain\InteractionRepository\ChunkInteractionRepository;
use Apisearch\Plugin\DBAL\Domain\InteractionRepository\DBALInteractionRepository;
use Apisearch\Plugin\DBAL\Domain\LogRepository\DBALLogRepository;
use Apisearch\Plugin\DBAL\Domain\SearchesRepository\ChunkSearchesRepository;
use Apisearch\Plugin\DBAL\Domain\SearchesRepository\DBALSearchesRepository;
use Apisearch\Plugin\DBAL\Domain\UsageRepository\ChunkUsageRepository;
use Apisearch\Plugin\DBAL\Domain\UsageRepository\DBALUsageRepository;
use Apisearch\Server\Domain\Repository\MetadataRepository\DiskMetadataRepository;
use Apisearch\Server\Domain\Repository\MetadataRepository\InMemoryMetadataRepository;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class DeletedUnusedRepositoriesCompilerPass.
 */
class DeletedUnusedRepositoriesCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $this->deleteRepositoriesIfDisabled(
            $container,
            [
                DBALTokenRepository::class,
            ],
            'apisearch_server.tokens_repository_enabled'
        );

        $this->deleteRepositoriesIfDisabled(
            $container,
            [
                DBALInteractionRepository::class,
                ChunkInteractionRepository::class,
            ],
            'apisearch_server.interactions_repository_enabled'
        );

        $this->deleteRepositoriesIfDisabled(
            $container,
            [
                DBALSearchesRepository::class,
                ChunkSearchesRepository::class,
            ],
            'apisearch_server.searches_repository_enabled'
        );

        $this->deleteRepositoriesIfDisabled(
            $container,
            [
                DBALUsageRepository::class,
                ChunkUsageRepository::class,
            ],
            'apisearch_server.usage_lines_repository_enabled'
        );

        $this->deleteRepositoriesIfDisabled(
            $container,
            [
                DBALLogRepository::class,
            ],
            'apisearch_server.logs_repository_enabled'
        );

        $this->deleteRepositories(
            $container,
            [
                DiskMetadataRepository::class,
                InMemoryMetadataRepository::class,
            ]
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string[]         $repositories
     * @param string           $parameter
     *
     * @return void
     */
    private function deleteRepositoriesIfDisabled(
        ContainerBuilder $container,
        array $repositories,
        string $parameter
    ): void {
        $enabled = $container->resolveEnvPlaceholders($container->getParameter($parameter));
        $enabled = \boolval($enabled);
        if (!$enabled) {
            foreach ($repositories as $repositoryId) {
                $container->removeDefinition($repositoryId);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string[]         $repositories
     *
     * @return void
     */
    private function deleteRepositories(
        ContainerBuilder $container,
        array $repositories
    ): void {
        foreach ($repositories as $repositoryId) {
            $container->removeDefinition($repositoryId);
        }
    }
}
