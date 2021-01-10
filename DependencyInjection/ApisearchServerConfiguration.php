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

namespace Apisearch\Server\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class ApisearchServerConfiguration.
 */
class ApisearchServerConfiguration extends BaseConfiguration
{
    /**
     * Configure the root node.
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->scalarNode('environment')
                    ->defaultValue('dev')
                ->end()
                ->scalarNode('god_token')
                    ->defaultValue('')
                ->end()
                ->scalarNode('readonly_token')
                    ->defaultValue('')
                ->end()
                ->scalarNode('health_check_token')
                    ->defaultValue('')
                ->end()
                ->scalarNode('ping_token')
                    ->defaultValue('')
                ->end()

                ->arrayNode('async_events')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultValue(false)
                        ->end()
                        ->scalarNode('events_exchange')
                            ->defaultValue('events')
                        ->end()

                        ->arrayNode('amqp')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('host')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('port')
                                    ->defaultValue('5672')
                                ->end()
                                ->scalarNode('user')
                                    ->defaultValue('guest')
                                ->end()
                                ->scalarNode('password')
                                    ->defaultValue('guest')
                                ->end()
                                ->scalarNode('vhost')
                                    ->defaultValue('/')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('repositories')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('tokens_repository_enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('interactions_repository_enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('searches_repository_enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('usage_lines_repository_enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('logs_repository_enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('metadata_disk_path')
                            ->defaultValue('/tmp/apisearch.repository.metadata.db')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('limitations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('number_of_results')
                            ->defaultValue(100)
                        ->end()
                        ->integerNode('number_of_logs_per_page')
                            ->defaultValue(50)
                        ->end()
                        ->arrayNode('token_endpoint_permissions')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('number_of_suggestions')
                            ->defaultValue(10)
                        ->end()

                        ->integerNode('number_of_bulk_items_in_a_request')
                            ->defaultValue(500)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('settings')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('register_god_usage')
                        ->defaultTrue()
                    ->end()
                ->end();
    }
}
