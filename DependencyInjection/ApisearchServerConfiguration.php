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
                ->scalarNode('ping_token')
                    ->defaultValue('')
                ->end()
                ->arrayNode('limitations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('number_of_results')
                            ->defaultValue(100)
                        ->end()
                    ->end()
                ->end();
    }
}
