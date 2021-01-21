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

namespace Apisearch\Server\DependencyInjection\CompilerPass;

use Apisearch\Server\Domain\Repository\AppRepository\EmptyTokenRepository;
use Apisearch\Server\Domain\Repository\AppRepository\TokenRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\EmptyInteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InteractionRepository;
use Apisearch\Server\Domain\Repository\LogRepository\EmptyLogRepository;
use Apisearch\Server\Domain\Repository\LogRepository\LogRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\EmptySearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\SearchesRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\EmptyUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\UsageRepository;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class EnabledRepositoriesCompilerPass.
 */
class EnabledRepositoriesCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $this->enableOrDisableRepository(
            $container,
            TokenRepository::class,
            EmptyTokenRepository::class,
            'apisearch_server.tokens_repository_enabled'
        );

        $this->enableOrDisableRepository(
            $container,
            InteractionRepository::class,
            EmptyInteractionRepository::class,
            'apisearch_server.interactions_repository_enabled'
        );

        $this->enableOrDisableRepository(
            $container,
            SearchesRepository::class,
            EmptySearchesRepository::class,
            'apisearch_server.searches_repository_enabled'
        );

        $this->enableOrDisableRepository(
            $container,
            UsageRepository::class,
            EmptyUsageRepository::class,
            'apisearch_server.usage_lines_repository_enabled'
        );

        $this->enableOrDisableRepository(
            $container,
            LogRepository::class,
            EmptyLogRepository::class,
            'apisearch_server.logs_repository_enabled'
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $class
     * @param string           $emptyRepositoryClass
     * @param string           $parameter
     *
     * @return void
     */
    private function enableOrDisableRepository(
        ContainerBuilder $container,
        string $class,
        string $emptyRepositoryClass,
        string $parameter
    ): void {
        $enabled = $container->resolveEnvPlaceholders($container->getParameter($parameter));
        $enabled = \boolval($enabled);
        if (!$enabled) {
            $container->setAlias($class, $emptyRepositoryClass);
        }
    }
}
