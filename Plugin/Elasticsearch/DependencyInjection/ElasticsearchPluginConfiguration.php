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

namespace Apisearch\Plugin\Elasticsearch\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class ElasticaPluginConfiguration.
 */
class ElasticsearchPluginConfiguration extends BaseConfiguration
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
            ->children()
                ->booleanNode('refresh_on_write')
                    ->defaultFalse()
                ->end()
                ->enumNode('version')
                    ->values(['6', '7'])
                    ->defaultValue('7')
                ->end()
                ->scalarNode('host')
                    ->defaultValue('')
                ->end()
                ->scalarNode('port')
                    ->defaultValue('9200')
                ->end()
                ->scalarNode('user')
                    ->defaultValue('')
                ->end()
                ->scalarNode('password')
                    ->defaultValue('')
                ->end()
            ->end();
    }
}
