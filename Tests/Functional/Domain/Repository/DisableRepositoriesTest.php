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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

use Apisearch\Server\Domain\Repository\AppRepository\EmptyTokenRepository;
use Apisearch\Server\Domain\Repository\AppRepository\InMemoryTokenRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\EmptyInteractionRepository;
use Apisearch\Server\Domain\Repository\InteractionRepository\InMemoryInteractionRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\EmptySearchesRepository;
use Apisearch\Server\Domain\Repository\SearchesRepository\InMemorySearchesRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\EmptyUsageRepository;
use Apisearch\Server\Domain\Repository\UsageRepository\InMemoryUsageRepository;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class DisableRepositoriesTest.
 */
class DisableRepositoriesTest extends ServiceFunctionalTest
{
    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);
        $configuration['parameters']['apisearch_server.tokens_repository_enabled'] = false;
        $configuration['parameters']['apisearch_server.interactions_repository_enabled'] = false;
        $configuration['parameters']['apisearch_server.searches_repository_enabled'] = false;
        $configuration['parameters']['apisearch_server.usage_lines_repository_enabled'] = false;

        return $configuration;
    }

    /**
     * Test repositories.
     *
     * @return void
     */
    public function testRepositories(): void
    {
        $this->assertInstanceOf(EmptyTokenRepository::class, $this->get('apisearch_server.tokens_repository_test'));
        $this->assertInstanceOf(EmptyInteractionRepository::class, $this->get('apisearch_server.interactions_repository_test'));
        $this->assertInstanceOf(EmptySearchesRepository::class, $this->get('apisearch_server.searches_repository_test'));
        $this->assertInstanceOf(EmptyUsageRepository::class, $this->get('apisearch_server.usage_lines_repository_test'));

        $this->assertFalse($this->has(InMemoryTokenRepository::class));
        $this->assertFalse($this->has(InMemoryUsageRepository::class));
        $this->assertFalse($this->has(InMemoryInteractionRepository::class));
        $this->assertFalse($this->has(InMemorySearchesRepository::class));
    }
}
