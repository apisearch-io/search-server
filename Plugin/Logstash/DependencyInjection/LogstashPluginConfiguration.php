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

namespace Apisearch\Plugin\Logstash\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class LogstashPluginConfiguration.
 */
class LogstashPluginConfiguration extends BaseConfiguration
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
                    ->scalarNode('key')
                        ->defaultValue('logstash.apisearch')
                    ->end()
                    ->scalarNode('service')
                        ->defaultValue('apisearch')
                    ->end();
    }
}
