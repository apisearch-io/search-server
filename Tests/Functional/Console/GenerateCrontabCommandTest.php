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

namespace Apisearch\Server\Tests\Functional\Console;

use Apisearch\Server\Tests\Functional\Console\Crontab\FakeCrontabMiddleware;

/**
 * Class GenerateCrontabCommandTest.
 */
class GenerateCrontabCommandTest extends CommandTest
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

        $configuration['services'][FakeCrontabMiddleware::class] = [
            'tags' => ['apisearch_plugin.middleware'],
        ];

        return $configuration;
    }

    /**
     * Test token creation.
     */
    public function testCrontabGeneration()
    {
        static::runCommand([
            'command' => 'apisearch-server:generate-crontab',
        ]);

        $this->assertEquals(
            sprintf('1 * * * * cd %s && blah1.sh', realpath(__DIR__.'/../../..')),
            exec('crontab -l')
        );
    }
}
