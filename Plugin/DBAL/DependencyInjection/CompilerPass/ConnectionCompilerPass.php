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

use Drift\DBAL\DependencyInjection\CompilerPass\ConnectionCompilerPass as DBALConnectionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ConnectionCompilerPass.
 */
class ConnectionCompilerPass extends DBALConnectionCompilerPass
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $this->createConnection(
            $container,
            'dbal_plugin',
            $container->getParameter('apisearch_plugin.dbal.configuration')
        );
    }
}
