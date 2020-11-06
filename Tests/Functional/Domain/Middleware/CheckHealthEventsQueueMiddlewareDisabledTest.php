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

namespace Apisearch\Search\Domain\Middleware;

use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class CheckHealthEventsQueueMiddlewareDisabledTest.
 */
class CheckHealthEventsQueueMiddlewareDisabledTest extends ServiceFunctionalTest
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
        $configuration['apisearch_server']['async_events']['enabled'] = false;

        return $configuration;
    }

    public function testHealthCheck()
    {
        $data = $this->checkHealth();
        $this->assertArrayNotHasKey('amqp', $data['status']);
        $this->assertArrayNotHasKey('amqp', $data['info']);
    }
}
