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

namespace Apisearch\Plugin\DBAL\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class DBALPluginConfiguration.
 */
class DBALPluginConfiguration extends BaseConfiguration
{
    /**
     * Configure the root node.
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()

                ->enumNode('driver')
                    ->values(['mysql', 'sqlite', 'postgres'])
                ->end()

                ->scalarNode('host')->end()
                ->integerNode('port')->end()
                ->scalarNode('user')->end()
                ->scalarNode('password')->end()
                ->scalarNode('dbname')->end()

                ->scalarNode('tokens_table')->end()
                ->scalarNode('index_configs_table')->end()
                ->scalarNode('usage_lines_table')->end()
                ->scalarNode('metadata_table')->end()
                ->scalarNode('interactions_table')->end()
                ->scalarNode('searches_table')->end()

                ->arrayNode('encrypt')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enabled')->end()
                        ->scalarNode('private_key')->end()
                        ->scalarNode('method')->defaultValue('aes128')->end()
                        ->scalarNode('iv')->end()
                    ->end()
                ->end()
                ->scalarNode('loop_push_interval')
                    ->defaultValue('60')
                ->end()

                ->booleanNode('locator_enabled')
                    ->defaultTrue()
                ->end();
    }
}
