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

namespace Apisearch\Plugin\Logstash\Tests\Functional;

use Apisearch\Plugin\Logstash\LogstashPluginBundle;

/**
 * Class HealthCheckMiddlewareTest.
 */
class HealthCheckMiddlewareTest extends LogstashFunctionalTest
{
    public function testHealthCheck(): void
    {
        $response = $this->checkHealth();
        $this->assertTrue($response['status']['redis_logstash']);
        $this->assertGreaterThan(0, $response['info']['redis_logstash']['ping_in_microseconds']);
        $this->assertTrue(\in_array(LogstashPluginBundle::class, $response['info']['plugins']));
    }
}
