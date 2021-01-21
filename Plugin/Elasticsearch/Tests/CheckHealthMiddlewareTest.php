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

namespace Apisearch\Plugin\Elasticsearch\Tests;

use Apisearch\Plugin\Elasticsearch\ElasticsearchPluginBundle;
use Apisearch\Server\Tests\Functional\ServiceFunctionalTest;

/**
 * Class CheckHealthMiddlewareTest.
 */
class CheckHealthMiddlewareTest extends ServiceFunctionalTest
{
    use ElasticFunctionalTestTrait;

    /**
     * Test health check.
     *
     * @return void
     */
    public function testCheckHealth(): void
    {
        $response = $this->checkHealth();
        $this->assertTrue(
            \in_array(
                $response['status']['elasticsearch'],
                ['green', 'yellow']
            )
        );

        $this->assertEquals(ElasticsearchPluginBundle::class, $response['info']['plugins']['elasticsearch']);
        $this->assertNotEmpty($response['info']['elasticsearch']);
        $this->assertEquals(3, $response['info']['elasticsearch']['number_of_indices']);
        $this->assertGreaterThan(0, $response['info']['elasticsearch']['ping_in_microseconds']);
    }
}
