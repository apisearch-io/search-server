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

namespace Apisearch\Plugin\JWT\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class JWTPluginConfiguration.
 */
class JWTPluginConfiguration extends BaseConfiguration
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
                    ->scalarNode('private_key')->end()
                    ->scalarNode('allowed_algorithms')->end()
                    ->integerNode('ttl')->end()
                    ->arrayNode('endpoints')
                        ->scalarPrototype()
                        ->end()
                        ->defaultValue(['query'])
                    ->end()
                    ->arrayNode('filters')
                        ->arrayPrototype()
                            ->arrayPrototype()
                                ->variablePrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end();
    }
}
