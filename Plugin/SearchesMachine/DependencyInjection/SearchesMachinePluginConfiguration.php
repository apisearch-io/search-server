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

namespace Apisearch\Plugin\SearchesMachine\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class SearchesMachinePluginConfiguration.
 */
class SearchesMachinePluginConfiguration extends BaseConfiguration
{
    /**
     * Configure the root node.
     *
     * @param ArrayNodeDefinition $rootNode Root node
     *
     * @return void
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('redis_host')->end()
                    ->scalarNode('redis_port')
                        ->defaultValue(6379)
                    ->end()
                    ->scalarNode('redis_database')
                        ->defaultValue('/')
                    ->end()
                    ->scalarNode('redis_password')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('redis_key')
                        ->defaultValue('searches_list')
                    ->end()
                    ->integerNode('minutes_interval_between_processing')
                        ->defaultValue(300)
                    ->end()
                ->end()
            ->end();
    }
}
